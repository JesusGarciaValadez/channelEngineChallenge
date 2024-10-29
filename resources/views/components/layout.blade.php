<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $title ?? 'ChannelEngine coding test' }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp,container-queries"></script>
</head>
<body>
    <div class="bg-white">
        <x-header/>

        {{ $slot }}

        <x-footer/>
    </div>
</body>
</html>
