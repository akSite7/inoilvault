<!DOCTYPE html>
<html>
  <head lang="ru">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>iNoil Test</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
      @theme {
        --font-sans: "Open Sans", "sans-serif";
        
            --breakpoint-2xl: 1600px;
            --breakpoint-xl: 1280px;
            --breakpoint-lg: 1024px;
            --breakpoint-md: 768px;
            --breakpoint-sm: 640px;
            --breakpoint-xs: 400px;
            --breakpoint-xxs: 320px;
        
            --color-base: #141414; /* РЎвЂћР С•Р Р… */
        
            --color-accent: #1c1c1c; /* РЎРѓР ВµРЎР‚РЎвЂ№Р в„– Р Т‘Р В»РЎРЏ Р В±Р В»Р С•Р С”Р С•Р Р† */
            --color-accent-hover: #272727;
        
            --color-primary: #4f39f6; /* РЎвЂћР С‘Р С•Р В» РЎвЂ Р Р†Р ВµРЎвЂљ */
            --color-primary-hover: #432dd7;  /* РЎвЂћР С‘Р С•Р В» РЎвЂ Р Р†Р ВµРЎвЂљ Р С—РЎР‚Р С‘ Р Р…Р В°Р Р†Р ВµР Т‘Р ВµР Р…Р С‘Р С‘ */
            
            --color-secondary: #272727; /* Р Т‘Р В»РЎРЏ Р В±Р В»Р С•Р С”Р С•Р Р† Р Р†Р Р…РЎС“РЎвЂљРЎР‚Р С‘ РЎРѓР ВµРЎР‚Р С•Р С–Р С• Р В±Р В»Р С•Р С”Р В° */
            --color-secondary-hover: #333335; 
        
            --color-input: #333335; /* Р Т‘Р В»РЎРЏ Р С‘Р Р…Р С—РЎС“РЎвЂљР С•Р Р† Р Р†Р Р…РЎС“РЎвЂљРЎР‚Р С‘ РЎРѓР ВµРЎР‚Р С•Р С–Р С• Р В±Р В»Р С•Р С”Р В° */
            --color-input-hover: #444445;
        
            --color-text-primary: #bfbfbf;
            --color-text-secondary: #838287;
      }
    </style>
    @vite('resources/js/app.js')
    @inertiaHead
  </head>
  <body class="bg-base text-text-primary flex flex-col min-h-screen flex-grow" style="scrollbar-gutter: stable;">
    @inertia
  </body>
</html>
