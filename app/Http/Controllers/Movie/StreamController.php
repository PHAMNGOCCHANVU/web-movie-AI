<?php

namespace App\Http\Controllers\Movie;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function stream(Request $request, $movieId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'require_login', 'message' => 'Vui lòng đăng nhập để xem phim.'], 401);
        }

        if ($user->is_locked) {
            return response()->json(['error' => 'account_locked', 'message' => 'Tài khoản đã bị khóa.'], 403);
        }

        // Auto-expire check
        if ($user->subscription_status === 'active' && $user->subscription_expires_at && $user->subscription_expires_at->isPast()) {
            $user->update(['subscription_status' => 'expired']);
        }

        if ($user->subscription_status !== 'active') {
            return response()->json(['error' => 'require_subscription', 'message' => 'Bạn cần đăng ký gói cước để xem phim.'], 403);
        }

        $movie = Movie::where('status', 'approved')->findOrFail($movieId);

        // VIP check for premium movies
        if ($movie->is_premium) {
            $isVip = $user->subscriptionPlan && str_starts_with($user->subscriptionPlan->plan_code, 'vip');
            if (! $isVip) {
                return response()->json(['error' => 'require_vip_upgrade', 'message' => 'Phim này yêu cầu gói VIP.'], 403);
            }
        }

        // Return stream URL
        $episodeId = $request->input('episode_id');

        if ($episodeId) {
            $episode = Episode::where('movie_id', $movie->id)->where('id', $episodeId)->firstOrFail();
            $streamUrl = $episode->link_m3u8 ?: $episode->link_embed;
            $streamType = $episode->link_m3u8 ? 'm3u8' : 'embed';
        } else {
            // Try first episode or movie stream_url
            $firstEpisode = Episode::where('movie_id', $movie->id)->orderBy('sort_order')->first();
            if ($firstEpisode) {
                $episodeId = $firstEpisode->id;
                $streamUrl = $firstEpisode->link_m3u8 ?: $firstEpisode->link_embed;
                $streamType = $firstEpisode->link_m3u8 ? 'm3u8' : 'embed';
            } else {
                $streamUrl = $movie->stream_url;
                $streamType = $movie->stream_source ?: 'm3u8';
            }
        }

        if (! $streamUrl) {
            return response()->json(['error' => 'stream_unavailable', 'message' => 'Nguồn phát hiện không khả dụng.'], 404);
        }

        // Increment view count
        $movie->increment('view_count');

        return response()->json([
            'data' => [
                'stream_url' => $streamUrl,
                'stream_type' => $streamType,
                'movie_id' => $movie->id,
                'movie_name' => $movie->name,
                'is_premium' => $movie->is_premium,
                'episode_id' => $episodeId ? (int) $episodeId : null,
            ],
        ]);
    }
}
