<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Motebele Systems POS</title>

    <meta name="description"
          content="Modern Point of Sale System">

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="{{ asset('css/landing.css') }}">

</head>

<body>

@include('components.navbar')

<main>

    @yield('content')

</main>

@include('components.footer')

<script src="{{ asset('js/landing.js') }}"></script>

</body>

</html>