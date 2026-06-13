<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#070a12">
        <meta name="description" content="CineON - Nền tảng xem phim và trợ lý AI gợi ý phim">

        <title>CineON</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800&display=swap" rel="stylesheet">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body>
        <script>
            (() => {
                const params = new URLSearchParams(window.location.search);
                const isVnPayReturn = params.has('vnp_TxnRef');
                const isReturnPath = window.location.pathname === '/payment/return';

                if ((isVnPayReturn || isReturnPath) && !window.location.hash.startsWith('#/payment/return')) {
                    const query = window.location.search;
                    window.location.replace(`${window.location.origin}/#/payment/return${query}`);
                }
            })();
        </script>
        <div id="app">
            <div class="app-boot">
                <div class="brand brand--large"><span>Cine</span><strong>ON</strong></div>
                <div class="boot-loader" aria-label="Đang tải"></div>
            </div>
        </div>
        <div id="toast-root" aria-live="polite"></div>
        <div id="modal-root"></div>
    </body>
</html>
