<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon de la marca -->
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='28' fill='%23a91721'/><text x='50%' y='70%' font-size='62' font-weight='900' font-family='system-ui,sans-serif' text-anchor='middle' fill='white'>Y</text></svg>">

        <!-- Fuentes tipográficas -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Prevenir parpadeo (FOUC) de tema y tamaño de fuente -->
        <script>
            (function() {
                try {
                    var theme = localStorage.getItem('ynentario_theme') || 'system';
                    var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }

                    var fontMap = {
                        small: '90%',
                        normal: '100%',
                        large: '112.5%',
                        huge: '125%'
                    };
                    var fontSize = localStorage.getItem('ynentario_font_size') || 'normal';
                    if (fontMap[fontSize]) {
                        document.documentElement.style.fontSize = fontMap[fontSize];
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Scripts de la aplicación -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
