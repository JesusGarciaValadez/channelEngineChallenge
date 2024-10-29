<x-layout>
    <x-slot:title>
        StartSelect coding test
    </x-slot>

    <div class="mx-auto mt-32 max-w-7xl px-6 sm:mt-56 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
            <h1 class="text-base font-semibold leading-7 text-indigo-600">ChannelEngine</h1>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Coding test</p>
            <p class="mt-6 text-lg leading-8 text-gray-600">Coding test for the Senior PHP Developer role.</p>
        </div>

        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
            <div class="space-y-12 sm:space-y-16">
                <div>
                    <div class="px-4 sm:px-6 lg:px-8">
                        <div class="sm:flex sm:items-center">
                            <div class="sm:flex-auto">
                                <h1 class="text-base font-semibold leading-6 text-gray-900">Top 5 Products by Quantity Sold</h1>
                                <p class="mt-2 text-sm text-gray-700">A table of top sold products.</p>
                            </div>
                        </div>
                        <div class="mt-8 flow-root">
                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                    @if(count($topProducts) <= 0)
                                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                                            No new products to show.
                                        </h2>
                                    @else
                                        @error('error')
                                        <div class="block text-sm font-medium leading-6 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                        @if(session('status'))
                                            <div @class([
                                                'block',
                                                'rounded-md',
                                                'p-3',
                                                'text-sm',
                                                'font-medium',
                                                'leading-6',
                                                'text-white',
                                                'bg-green-600' => session('status') === 'success',
                                                'bg-red-600' => session('status') === 'error',
                                            ])>
                                                {{ session('status_message') }}
                                            </div>
                                        @endif

                                        <x-table :headers="['Product Name', 'GTIN', 'Total Quantity Sold', 'Actions']">
                                            <x-slot:rows>
                                                @foreach($topProducts as $product)
                                                    <tr>
                                                        <td class="whitespace-nowrap px-2 py-2 text-sm text-gray-900">
                                                            {{ $product->description }}
                                                        </td>
                                                        <td class="whitespace-nowrap px-2 py-2 text-sm text-gray-900">
                                                            {{ $product->gtin ?? 'N/A' }}
                                                        </td>
                                                        <td class="whitespace-nowrap px-2 py-2 text-sm text-gray-900
                                                        text-center">
                                                            {{ $product->total_quantity_sold }}
                                                        </td>
                                                        <td class="whitespace-nowrap px-2 py-2 text-sm text-gray-900
                                                        text-right">
                                                            <form action="{{ route('order_lines.update',
                                                            $product->id) }}" method="POST">
                                                                @csrf
                                                                @method('PUT')
                                                                <button
                                                                    class="inline-flex justify-center rounded-md
                                                                    bg-red-600 px-3 py-2 text-sm font-semibold
                                                                    text-white shadow-sm hover:bg-red-500
                                                                    focus-visible:outline focus-visible:outline-2
                                                                    focus-visible:outline-offset-2 focus-visible:outline-red-600"
                                                                    type="submit"
                                                                >
                                                                        Update Stock to 25
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </x-slot>
                                        </x-table>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
