<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>NannyLink MVP</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b', // Amber-500 (Primary)
                            600: '#d97706',
                            700: '#b45309',
                        },
                        safety: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669', // Emerald-600 (Secondary)
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    
    <style>
        :root {
            --primary-color: #f59e0b;
        }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 16px; /* Prevents auto-zoom on iOS Safari */
        }
        .touch-target {
            min-height: 44px;
            min-width: 44px;
        }
    </style>
</head>
<body class="h-full text-slate-900 antialiased flex flex-col">

    <!-- Offline Notification Bar -->
    <div id="offline-bar" class="hidden fixed top-0 left-0 right-0 bg-red-500 text-white text-xs font-semibold py-2 text-center z-50 transition-all duration-300">
        Проблемы с соединением. Пытаемся восстановить подключение...
    </div>

    <!-- Main Content Container -->
    <main class="flex-1 flex flex-col justify-between max-w-md mx-auto w-full bg-white shadow-md overflow-y-auto relative min-h-screen">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <!-- Global JS utilities -->
    <script>
        // Offline / Online detection
        window.addEventListener('offline', () => {
            document.getElementById('offline-bar').classList.remove('hidden');
        });
        window.addEventListener('online', () => {
            document.getElementById('offline-bar').classList.add('hidden');
        });
        if (!navigator.onLine) {
            document.getElementById('offline-bar').classList.remove('hidden');
        }

        // Telegram WebApp theme sync
        if (window.Telegram && window.Telegram.WebApp) {
            const tg = window.Telegram.WebApp;
            tg.ready();
            tg.expand();

            // Apply Telegram theme colors dynamically
            const buttonColor = tg.themeParams?.button_color;
            if (buttonColor) {
                document.documentElement.style.setProperty('--primary-color', buttonColor);
            }

            if (tg.colorScheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        }

        // Global Helper for API requests
        async function apiRequest(url, method = 'GET', data = null) {
            const token = localStorage.getItem('auth_token');
            const lang = localStorage.getItem('language') || 'ru';
            
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Accept-Language': lang
            };
            
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const config = { method, headers };
            if (data && method !== 'GET') {
                config.body = JSON.stringify(data);
            }

            const response = await fetch(url, config);
            
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                window.location.href = '/login';
                return null;
            }

            const result = await response.json();
            if (!response.ok) {
                throw { status: response.status, data: result };
            }
            return result;
        }
    </script>

    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>