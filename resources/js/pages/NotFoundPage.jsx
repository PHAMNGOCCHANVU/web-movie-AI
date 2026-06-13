import React from 'react';
import { Link } from 'react-router-dom';
import Brand from '../components/Brand';

export default function NotFoundPage() {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-[#070a12] px-4 text-center text-white">
            <Brand large />
            <strong className="mt-12 text-8xl text-white/10">404</strong>
            <h1 className="mt-4 text-3xl font-bold">Không tìm thấy trang</h1>
            <Link className="button button--primary mt-7" to="/">Quay về Trang chủ</Link>
        </div>
    );
}
