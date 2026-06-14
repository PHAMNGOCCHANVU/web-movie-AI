<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyPasswordOtpRequest;
use App\Http\Requests\Auth\VerifyRegistrationOtpRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $otp = (string) random_int(100000, 999999);

        DB::table('registration_otps')->updateOrInsert(
            ['email' => $email],
            [
                'name' => $request->string('name')->toString(),
                'password' => Hash::make($request->string('password')->toString()),
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Notification::route('mail', $email)
            ->notify(new RegistrationOtpNotification($otp));

        return response()->json([
            'message' => 'Mã OTP xác thực đã được gửi đến email và có hiệu lực trong 10 phút.',
            'data' => ['email' => $email],
        ], 202);
    }

    public function verifyRegistrationOtp(VerifyRegistrationOtpRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $record = DB::table('registration_otps')->where('email', $email)->first();

        if (! $record || now()->greaterThan($record->expires_at) || $record->attempts >= 5) {
            return response()->json([
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.',
                'error' => 'invalid_otp',
            ], 422);
        }

        if (! Hash::check($request->string('otp')->toString(), $record->otp_hash)) {
            DB::table('registration_otps')->where('email', $email)->increment('attempts');

            return response()->json([
                'message' => 'Mã OTP không chính xác.',
                'error' => 'invalid_otp',
            ], 422);
        }

        if (User::where('email', $email)->exists()) {
            DB::table('registration_otps')->where('email', $email)->delete();

            return response()->json([
                'message' => 'Email này đã được sử dụng.',
                'error' => 'email_exists',
            ], 422);
        }

        $user = DB::transaction(function () use ($record, $email) {
            $userRole = Role::firstOrCreate(['name' => 'user']);

            $user = User::create([
                'name' => $record->name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $record->password,
                'role_id' => $userRole->id,
                'subscription_status' => 'none',
                'auto_renew' => false,
            ]);

            DB::table('registration_otps')->where('email', $email)->delete();

            return $user;
        });
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Xác thực email và đăng ký tài khoản thành công.',
            'data' => [
                'user' => $user->load('role'),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác.',
                'error' => 'invalid_credentials',
            ], 401);
        }

        if ($user->is_locked) {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.',
                'error' => 'account_locked',
            ], 403);
        }

        if ($request->boolean('revoke_old_tokens')) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'data' => [
                'user' => $user->load(['role', 'subscriptionPlan']),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load(['role', 'subscriptionPlan']),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);

            DB::table('password_reset_otps')->updateOrInsert(
                ['email' => $email],
                [
                    'otp_hash' => Hash::make($otp),
                    'reset_token_hash' => null,
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(10),
                    'verified_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Notification::route('mail', $email)
                ->notify(new PasswordResetOtpNotification($otp));
        }

        return response()->json([
            'message' => 'Nếu email tồn tại, mã OTP 6 số đã được gửi và có hiệu lực trong 10 phút.',
        ]);
    }

    public function verifyPasswordOtp(VerifyPasswordOtpRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $record = DB::table('password_reset_otps')->where('email', $email)->first();

        if (! $record || now()->greaterThan($record->expires_at) || $record->attempts >= 5) {
            return response()->json([
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.',
                'error' => 'invalid_otp',
            ], 422);
        }

        if (! Hash::check($request->string('otp')->toString(), $record->otp_hash)) {
            DB::table('password_reset_otps')->where('email', $email)->increment('attempts');

            return response()->json([
                'message' => 'Mã OTP không chính xác.',
                'error' => 'invalid_otp',
            ], 422);
        }

        $resetToken = Str::random(64);
        DB::table('password_reset_otps')
            ->where('email', $email)
            ->update([
                'reset_token_hash' => hash('sha256', $resetToken),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Xác thực OTP thành công.',
            'data' => ['reset_token' => $resetToken],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $record = DB::table('password_reset_otps')->where('email', $email)->first();
        $validToken = $record
            && $record->verified_at
            && now()->lessThanOrEqualTo($record->expires_at)
            && hash_equals(
                (string) $record->reset_token_hash,
                hash('sha256', $request->string('reset_token')->toString())
            );

        $user = $validToken ? User::where('email', $email)->first() : null;
        if (! $user) {
            return response()->json([
                'message' => 'Phiên đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
                'error' => 'invalid_reset_token',
            ], 422);
        }

        DB::transaction(function () use ($user, $request, $email) {
            $user->forceFill([
                'password' => Hash::make($request->string('password')->toString()),
            ])->setRememberToken(Str::random(60));
            $user->save();
            $user->tokens()->delete();
            DB::table('password_reset_otps')->where('email', $email)->delete();
        });

        return response()->json([
            'message' => 'Mật khẩu đã được đặt lại thành công.',
        ]);
    }
}
