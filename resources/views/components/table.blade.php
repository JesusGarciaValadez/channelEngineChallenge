@props(['headers'])

<table class="min-w-full divide-y divide-gray-300">
    <thead>
    <tr>
        @foreach($headers as $header)
            <th scope="col"
                class="whitespace-nowrap px-2 py-3.5 text-center text-sm font-semibold text-gray-900">{{ $header }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 bg-white">
    {{ $rows }}
    </tbody>
</table>
