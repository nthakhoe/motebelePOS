<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name'))</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

    @filamentStyles

</head>

<body>

    @yield('content')

    @livewireScripts

    @filamentScripts

</body>

</html>