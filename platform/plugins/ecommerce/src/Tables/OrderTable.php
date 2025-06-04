<?php

namespace Botble\Ecommerce\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Ecommerce\Enums\OrderHistoryActionEnum;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\Ecommerce\Tables\Formatters\PriceFormatter;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Actions\Action;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\StatusColumn;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Order::class)
            ->addActions([
                // Standard edit and delete actions
                EditAction::make()->route('orders.edit'),
                DeleteAction::make()->route('orders.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->addIndexColumn()
            ->editColumn('payment_status', function (Order $item) {
                if (! is_plugin_active('payment')) {
                    return '&mdash;';
                }

                return $item->payment->status->label() ? BaseHelper::clean(
                    $item->payment->status->toHtml()
                ) : '&mdash;';
            })
            ->editColumn('payment_method', function (Order $item) {
                if (! is_plugin_active('payment')) {
                    return '&mdash;';
                }

                return BaseHelper::clean($item->payment->payment_channel->label() ?: '&mdash;');
            })
            ->formatColumn('amount', PriceFormatter::class)
            ->editColumn('shipping_amount', function (Order $item) {
                return $item->shipment->exists() ? $item->shipping_amount : '&mdash;';
            })
            ->editColumn('products', function (Order $item) {
                $products = $item->products;
                
                if ($products->isEmpty()) {
                    return '&mdash;';
                }
                
                $output = '<div class="product-list">';
                foreach ($products as $product) {
                    $output .= '<div class="product-item">- ' . e($product->product_name) . ' (' . $product->qty . ')</div>';
                }
                $output .= '</div>';
                
                return $output;
            });

        if (EcommerceHelper::isTaxEnabled()) {
            $data = $data->formatColumn('tax_amount', PriceFormatter::class);
        }

        $data = $data
            ->filter(function ($query) {
                if ($keyword = $this->request->input('search.value')) {
                    $keyword = '%' . $keyword . '%';

                    return $query
                        ->whereHas('address', function ($subQuery) use ($keyword) {
                            return $subQuery
                                ->where('name', 'LIKE', $keyword)
                                ->orWhere('email', 'LIKE', $keyword)
                                ->orWhere('phone', 'LIKE', $keyword);
                        })
                        ->orWhereHas('user', function ($subQuery) use ($keyword) {
                            return $subQuery
                                ->where('name', 'LIKE', $keyword)
                                ->orWhere('email', 'LIKE', $keyword)
                                ->orWhere('phone', 'LIKE', $keyword);
                        })
                        ->orWhere('code', 'LIKE', $keyword);
                }

                return $query;
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $with = ['user', 'shipment', 'address'];

        if (is_plugin_active('payment')) {
            $with[] = 'payment';
        }

        $query = $this
            ->getModel()
            ->query()
            ->with($with)
            ->select([
                'id',
                'status',
                'user_id',
                'created_at',
                'amount',
                'tax_amount',
                'shipping_amount',
                'payment_id',
            ])
            ->where('is_finished', 1);

        // Filter to show only today's orders by default
        // Only apply this filter if no explicit date filter is set in the request
        if (!$this->request->filled('filter_columns')) {
            $today = now()->startOfDay()->toDateTimeString();
            $endOfDay = now()->endOfDay()->toDateTimeString();
            $query->whereBetween('created_at', [$today, $endOfDay]);
        }

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        $columns = [
            Column::make('DT_RowIndex')
                ->title(trans('core/base::tables.id'))
                ->alignStart()
                ->orderable(false)
                ->searchable(false),
            FormattedColumn::make('created_at')
                ->title('Order time')
                ->renderUsing(function (FormattedColumn $column) {
                    $date = BaseHelper::formatDate($column->getItem()->created_at);
                    $time = $column->getItem()->created_at->format('H:i');
                    return $date . ' - ' . $time;
                }),
            FormattedColumn::make('user_id')
                ->title('User Info')
                ->alignStart()
                ->orderable(false)
                ->renderUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();
                    $name = $item->user->name ?: $item->address->name;
                    $phone = $item->user->phone ?: $item->address->phone;
                    $state = $item->address->state ?? '';
                    $address = $item->address->address ?? '';
                    
                    // Get state name if it's a numeric ID
                    $stateName = $state;
                    $isPhomPenh = ($state == '15' || $state == 15 || $state === 'Phnom Penh');
                    
                    if ($isPhomPenh) {
                        $stateName = 'Phnom Penh';
                    } else {
                        // Try to get state name from available states
                        $states = EcommerceHelper::getAvailableStatesByCountry($item->address->country ?? EcommerceHelper::getDefaultCountryId());
                        $stateName = $states[$state] ?? $state;
                        // Double-check if the resolved state name is Phnom Penh
                        if ($stateName === 'Phnom Penh') {
                            $isPhomPenh = true;
                        }
                    }
                    
                    // Improved formatting with styling
                    $userInfo = '<div class="user-info-container">';
                    $userInfo .= '<div class="user-info-item"><strong>Name:</strong> ' . $name . '</div>';
                    $userInfo .= '<div class="user-info-item"><strong>Phone:</strong> ' . $phone . '</div>';
                    
                    if ($state) {
                        $userInfo .= '<div class="user-info-item"><strong>Location:</strong> ' . $stateName . '</div>';
                        
                        // REVERSED: Only show address if state IS Phnom Penh
                        if ($isPhomPenh && $address) {
                            $userInfo .= '<div class="user-info-item"><strong>Address:</strong> ' . $address . '</div>';
                        }
                    } elseif ($address) {
                        // If no state but address exists, show address
                        $userInfo .= '<div class="user-info-item"><strong>Address:</strong> ' . $address . '</div>';
                    }
                    
                    $userInfo .= '</div>';
                    
                    return $userInfo;
                }),
            FormattedColumn::make('products')
                ->title('Product')
                ->alignStart()
                ->orderable(false)
                ->renderUsing(function (FormattedColumn $column) {
                    $item = $column->getItem();
                    $products = $item->products;
                    
                    if ($products->isEmpty()) {
                        return '&mdash;';
                    }
                    
                    $output = '<div class="product-list" style="max-width: 250px;">';
                    foreach ($products as $index => $product) {
                        $productName = e($product->product_name);
                        $qty = $product->qty;
                        
                        // Add a nice style with product name and quantity
                        $output .= '<div class="product-item" style="margin-bottom: 5px; padding: 3px 0;">';
                        $output .= '<span style="font-weight: 500;">' . $productName . '</span>';
                        $output .= '<span style="background-color: #f0f0f0; border-radius: 10px; padding: 2px 8px; margin-left: 5px; font-size: 12px;">' . $qty . '</span>';
                        $output .= '</div>';
                        
                        // Add a separator if not the last item
                        if ($index < count($products) - 1) {
                            $output .= '<div style="border-bottom: 1px dashed #eee; margin: 5px 0;"></div>';
                        }
                    }
                    $output .= '</div>';
                    
                    return $output;
                }),
            Column::formatted('amount')
                ->title(trans('plugins/ecommerce::order.amount')),
        ];

        // Payment method and payment status columns commented out as requested
        // Can be uncommented in the future if needed
        /*
        if (is_plugin_active('payment')) {
            $columns = array_merge($columns, [
                Column::make('payment_method')
                    ->name('payment_id')
                    ->title(trans('plugins/ecommerce::order.payment_method'))
                    ->alignStart(),
                Column::make('payment_status')
                    ->name('payment_id')
                    ->title(trans('plugins/ecommerce::order.payment_status_label')),
            ]);
        }
        */

        $columns[] = StatusColumn::make()->alignStart();

        if (EcommerceHelper::isTaxEnabled()) {
            $columns = array_merge($columns, [
                Column::formatted('tax_amount')
                    ->title(trans('plugins/ecommerce::order.tax_amount')),
            ]);
        }

        // Shipping amount column removed as requested

        // Add Quick Action column with custom buttons
        $columns[] = FormattedColumn::make('quick_actions')
            ->title('Quick Action')
            ->alignCenter()
            ->orderable(false)
            ->searchable(false)
            ->renderUsing(function (FormattedColumn $column) {
                $item = $column->getItem();
                $printInvoiceUrl = route('orders.generate-invoice', ['order' => $item->id]);
                $markPaidUrl = route('orders.edit', ['order' => $item->id]);
                
                return view('plugins/ecommerce::orders.quick-actions', compact('printInvoiceUrl', 'markPaidUrl'))->render();
            });

        return $columns;
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('orders.create'), 'orders.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('orders.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => OrderStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', OrderStatusEnum::values()),
            ],
            'created_at' => [
                'title' => 'Order Date',
                'type' => 'datePicker',
            ],
        ];
    }

    public function getFilters(): array
    {
        $filters = parent::getFilters();

        $filters = array_merge($filters, [
            'customer_name' => [
                'title' => trans('plugins/ecommerce::ecommerce.customer_name'),
                'type' => 'text',
            ],
            'customer_email' => [
                'title' => trans('plugins/ecommerce::ecommerce.customer_email'),
                'type' => 'text',
            ],
            'customer_phone' => [
                'title' => trans('plugins/ecommerce::ecommerce.customer_phone'),
                'type' => 'text',
            ],
            'amount' => [
                'title' => trans('plugins/ecommerce::order.amount'),
                'type' => 'number',
            ],
            'shipping_method' => [
                'title' => trans('plugins/ecommerce::ecommerce.shipping_method'),
                'type' => 'select',
                'choices' => array_filter(ShippingMethodEnum::labels()),
            ],
        ]);

        if (is_plugin_active('payment')) {
            $filters = array_merge($filters, [
                'payment_method' => [
                    'title' => trans('plugins/ecommerce::order.payment_method'),
                    'type' => 'select',
                    'choices' => PaymentMethodEnum::labels(),
                ],
                'payment_status' => [
                    'title' => trans('plugins/ecommerce::order.payment_status_label'),
                    'type' => 'select',
                    'choices' => PaymentStatusEnum::labels(),
                ],
            ]);
        }

        if (is_plugin_active('marketplace')) {
            $filters['store_id'] = [
                'title' => trans('plugins/marketplace::store.forms.store'),
                'type' => 'select-search',
                'choices' => [-1 => theme_option('site_title')] + DB::table('mp_stores')->pluck('name', 'id')->all(),
            ];
        }

        return $filters;
    }

    public function renderTable($data = [], $mergeData = []): View|Factory|Response
    {
        if ($this->isEmpty()) {
            return view('plugins/ecommerce::orders.intro');
        }

        return parent::renderTable($data, $mergeData);
    }

    public function getDefaultButtons(): array
    {
        return array_merge(['export'], parent::getDefaultButtons());
    }

    public function saveBulkChangeItem(Model|Order $item, string $inputKey, ?string $inputValue): Model|bool
    {
        if ($inputKey === 'status' && $inputValue == OrderStatusEnum::CANCELED) {
            /**
             * @var Order $item
             */
            if (! $item->canBeCanceledByAdmin()) {
                throw new Exception(trans('plugins/ecommerce::order.order_cannot_be_canceled'));
            }

            OrderHelper::cancelOrder($item);

            OrderHistory::query()->create([
                'action' => OrderHistoryActionEnum::CANCEL_ORDER,
                'description' => trans('plugins/ecommerce::order.order_was_canceled_by'),
                'order_id' => $item->getKey(),
                'user_id' => Auth::id(),
            ]);

            return $item;
        }

        return parent::saveBulkChangeItem($item, $inputKey, $inputValue);
    }

    public function applyFilterCondition(
        Builder|QueryBuilder|Relation $query,
        string $key,
        string $operator,
        ?string $value
    ): Builder|QueryBuilder|Relation {
        switch ($key) {
            case 'customer_name':
                if (! $value) {
                    break;
                }

                return $this->filterByCustomer($query, 'name', $operator, $value);
            case 'customer_email':
                if (! $value) {
                    break;
                }

                return $this->filterByCustomer($query, 'email', $operator, $value);
            case 'customer_phone':
                if (! $value) {
                    break;
                }

                return $this->filterByCustomer($query, 'phone', $operator, $value);
            case 'status':
                if (! OrderStatusEnum::isValid($value)) {
                    return $query;
                }

                break;
            case 'shipping_method':
                if (! $value) {
                    break;
                }

                if (! ShippingMethodEnum::isValid($value)) {
                    return $query;
                }

                break;
            case 'payment_method':
                if (! is_plugin_active('payment') || ! PaymentMethodEnum::isValid($value)) {
                    return $query;
                }

                return $query->whereHas('payment', function ($subQuery) use ($value): void {
                    $subQuery->where('payment_channel', $value);
                });

            case 'payment_status':
                if (! is_plugin_active('payment') || ! PaymentStatusEnum::isValid($value)) {
                    return $query;
                }

                return $query->whereHas('payment', function ($subQuery) use ($value): void {
                    $subQuery->where('status', $value);
                });
            case 'store_id':
                if (! is_plugin_active('marketplace')) {
                    return $query;
                }
                if ($value == -1) {
                    return $query->where(function ($subQuery): void {
                        $subQuery->whereNull('store_id')
                            ->orWhere('store_id', 0);
                    });
                }
        }

        return parent::applyFilterCondition($query, $key, $operator, $value);
    }

    protected function filterByCustomer(
        Builder|QueryBuilder|Relation $query,
        string $column,
        string $operator,
        ?string $value
    ): Builder|QueryBuilder|Relation {
        if ($operator === 'like') {
            $value = '%' . $value . '%';
        } elseif ($operator !== '=') {
            $operator = '=';
        }

        return $query
            ->where(function ($query) use ($column, $operator, $value): void {
                $query
                    ->whereHas('user', function ($subQuery) use ($column, $operator, $value): void {
                        $subQuery->where($column, $operator, $value);
                    })
                    ->orWhereHas('address', function ($subQuery) use ($column, $operator, $value): void {
                        $subQuery->where($column, $operator, $value);
                    });
            });
    }
}
