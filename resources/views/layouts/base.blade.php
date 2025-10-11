<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-zinc-800 dark:bg-zinc-900 min-h-screen" data-user-id="{{ request()->cookie('user_token')}}">
        <div class="flex flex-col min-h-screen mt-3 p-2">
            {{ $slot }}
        </div>
        @fluxScripts
    </body>
</html>
