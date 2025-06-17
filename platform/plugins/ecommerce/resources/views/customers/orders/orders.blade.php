<x-core::table>
    <x-core::table.header>
        <x-core::table.header.cell>
            #
        </x-core::table.header.cell>
        <x-core::table.header.cell>
            {{ trans('plugins/ecommerce::order.order_id') }}
        </x-core::table.header.cell>
        <x-core::table.header.cell>
            {{ trans('plugins/ecommerce::order.amount') }}
        </x-core::table.header.cell>
        <x-core::table.header.cell>
            {{ trans('plugins/ecommerce::order.products') }}  
        </x-core::table.header.cell>
        <x-core::table.header.cell>
            {{ trans('plugins/ecommerce::order.status') }}
        </x-core::table.header.cell>

    </x-core::table.header>

    <x-core::table.body>
        @forelse ($orders as $order)
            <x-core::table.body.row>
                <x-core::table.body.cell>
                    {{ $loop->iteration }}
                </x-core::table.body.cell>
                <x-core::table.body.cell class="text-start">
                    @if (auth()->user()->hasPermission('orders.edit'))
                        <a href="{{ route('orders.edit', $order->id) }}" target="_blank">
                            {{ $order->code }} <x-core::icon name="ti ti-external-link" />
                        </a>
                    @else
                        {{ $order->code }}
                    @endif
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    {{ format_price($order->amount) }}
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    @if ($order->products->count())
                        <div class="product-list">
                            @foreach($order->products as $orderProduct)
                                <div class="product-item mb-1">
                                    <span class="product-name fw-medium">{{ $orderProduct->product_name }}</span>
                                    <span class="badge bg-light text-dark ms-1">x{{ $orderProduct->qty }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        &mdash;
                    @endif
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    {!! BaseHelper::clean($order->status->toHtml()) !!}
                </x-core::table.body.cell>

            </x-core::table.body.row>
        @empty
            <x-core::table.body.row class="text-center text-muted">
                <x-core::table.body.cell colspan="5">
                    {{ trans('plugins/ecommerce::order.no_data') }}
                </x-core::table.body.cell>
            </x-core::table.body.row>
        @endforelse
    </x-core::table.body>
</x-core::table>
