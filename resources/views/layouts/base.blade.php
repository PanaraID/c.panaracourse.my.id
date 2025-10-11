<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body data-user-id="{{ request()->cookie('user_token')}}">
        {{ $slot }}
        @fluxScripts
    </body>
</html>
