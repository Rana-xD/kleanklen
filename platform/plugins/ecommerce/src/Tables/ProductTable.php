<?php

namespace Botble\Ecommerce\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\DataSynchronize\Table\HeaderActions\ExportHeaderAction;
use Botble\DataSynchronize\Table\HeaderActions\ImportHeaderAction;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Enums\StockStatusEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\IsFeaturedBulkChange;
use Botble\Table\BulkChanges\NameBulkChange;
use Botble\Table\BulkChanges\NumberBulkChange;
use Botble\Table\BulkChanges\StatusBulkChange;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\ImageColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Product::class)
            ->addActions([
                EditAction::make()->route('products.edit'),
                DeleteAction::make()->route('products.destroy'),
            ])
            ->addHeaderActions([
                ExportHeaderAction::make()
                    ->route('tools.data-synchronize.export.products.index')
                    ->permission('ecommerce.export.products.index'),
                ImportHeaderAction::make()
                    ->route('tools.data-synchronize.import.products.index')
                    ->permission('ecommerce.import.products.index'),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('products.destroy'),
            ])
            ->addColumns([
                Column::make('DT_RowIndex')
                    ->title(trans('core/base::tables.id'))
                    ->alignStart()
                    ->orderable(false)
                    ->searchable(false),
                ImageColumn::make(),
                Column::make('barcode')
                    ->title('Barcode')
                    ->alignStart(),
                Column::make('name')
                    ->title(trans('plugins/ecommerce::products.name'))
                    ->alignStart(),
                Column::make('brand_name')
                    ->title('Brand')
                    ->alignStart()
                    ->getValueUsing(function (Column $column) {
                        return $column->getItem()->brand_name ?: '—';
                    }),
                Column::make('cost_per_item')
                    ->title('Cost')
                    ->alignStart()
                    ->getValueUsing(function (Column $column) {
                        $cost = $column->getItem()->cost_per_item;
                        return $cost ? '$' . number_format($cost, 2) : '$0.00';
                    }),
                Column::make('price')
                    ->title(trans('plugins/ecommerce::products.price'))
                    ->alignStart(),
                Column::make('stock_status')
                    ->title(trans('plugins/ecommerce::products.stock_status')),
                Column::make('quantity')
                    ->title(trans('plugins/ecommerce::products.quantity'))
                    ->alignStart(),
                StatusColumn::make()->title('Status'),
                FormattedColumn::make('quick_actions')
                    ->title('Quick Actions')
                    ->alignCenter()
                    ->orderable(false)
                    ->searchable(false)
                    ->renderUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();
                        $currentStatus = $item->status;
                        $newStatus = $currentStatus == BaseStatusEnum::PUBLISHED ? BaseStatusEnum::DRAFT : BaseStatusEnum::PUBLISHED;
                        $buttonText = $currentStatus == BaseStatusEnum::PUBLISHED ? 'Set Draft' : 'Publish';
                        $buttonClass = $currentStatus == BaseStatusEnum::PUBLISHED ? 'btn-warning' : 'btn-success';
                        $buttonIcon = $currentStatus == BaseStatusEnum::PUBLISHED ? 'archive' : 'check-circle';
                        
                        $output = '<div class="table-actions" style="display: flex; justify-content: center; gap: 8px;">';
                        
                        // Toggle Status button
                        $output .= '<a href="' . route('products.toggle-status', ['id' => $item->id]) . '" class="btn btn-icon btn-sm ' . $buttonClass . '" data-bs-toggle="tooltip" data-bs-original-title="' . $buttonText . '">';
                        if ($buttonIcon == 'archive') {
                            $output .= '<svg class="icon svg-icon-ti-ti-archive" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
                            $output .= '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>';
                            $output .= '<path d="M3 4m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path>';
                            $output .= '<path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-10"></path>';
                            $output .= '<path d="M10 12l4 0"></path>';
                            $output .= '</svg>';
                        } else {
                            $output .= '<svg class="icon svg-icon-ti-ti-check-circle" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
                            $output .= '<path stroke="none" d="M0 0h24v24H0z" fill="none"></path>';
                            $output .= '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>';
                            $output .= '<path d="M9 12l2 2l4 -4"></path>';
                            $output .= '</svg>';
                        }
                        $output .= '</a>';
                        
                        $output .= '</div>';
                        
                        return $output;
                    }),
            ])
            ->queryUsing(function (Builder $query) {
                return $query
                    ->select([
                        'ec_products.id',
                        'ec_products.name',
                        'ec_products.order',
                        'ec_products.created_at',
                        'ec_products.status',
                        'ec_products.barcode',
                        'ec_products.image',
                        'ec_products.images',
                        'ec_products.price',
                        'ec_products.sale_price',
                        'ec_products.sale_type',
                        'ec_products.start_date',
                        'ec_products.end_date',
                        'ec_products.quantity',
                        'ec_products.with_storehouse_management',
                        'ec_products.stock_status',
                        'ec_products.product_type',
                        'ec_products.brand_id',
                        'ec_products.cost_per_item',
                        'ec_brands.name as brand_name',
                    ])
                    ->leftJoin('ec_brands', 'ec_products.brand_id', '=', 'ec_brands.id')
                    ->where('ec_products.is_variation', 0);
            });
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->addIndexColumn() // This adds DT_RowIndex for sequential numbering
            ->editColumn('name', function (Product $item) {
                $productType = null;

                if (! EcommerceHelper::isDisabledPhysicalProduct() && EcommerceHelper::isEnabledSupportDigitalProducts()) {
                    $productType = Html::tag('small', ' &mdash; ' . $item->product_type->label())->toHtml();
                }

                if (! EcommerceHelper::isDisabledPhysicalProduct() && ! $this->hasPermission('products.edit')) {
                    return BaseHelper::clean($item->name) . $productType;
                }

                return Html::link(
                    route('products.edit', $item->getKey()),
                    BaseHelper::clean($item->name)
                ) . $productType;
            })
            ->editColumn('price', function (Product $item) {
                return $item->price_in_table;
            })
            ->editColumn('quantity', function (Product $item) {
                if (! $item->with_storehouse_management) {
                    return '&#8734;';
                }

                if ($item->variations->isEmpty()) {
                    return $item->quantity;
                }

                $withStoreHouseManagement = $item->with_storehouse_management;

                $quantity = 0;

                foreach ($item->variations as $variation) {
                    if (! $variation->product->with_storehouse_management) {
                        $withStoreHouseManagement = false;

                        break;
                    }

                    $quantity += $variation->product->quantity;
                }

                return $withStoreHouseManagement ? $quantity : '&#8734;';
            })
            ->editColumn('sku', function (Product $item) {
                return BaseHelper::clean($item->sku ?: '&mdash;');
            })
            ->editColumn('order', function (Product $item) {
                return view('plugins/ecommerce::products.partials.sort-order', compact('item'))->render();
            })
            ->editColumn('stock_status', function (Product $item) {
                return BaseHelper::clean($item->stock_status_html);
            })
            ->filter(function ($query) {
                $keyword = request()->input('search.value');
                if ($keyword) {
                    $keyword = '%' . $keyword . '%';

                    $query
                        ->where(function ($query) use ($keyword): void {
                            $query
                                ->where('ec_products.name', 'LIKE', $keyword)
                                ->where('is_variation', 0);
                        })
                        ->orWhere(function ($query) use ($keyword): void {
                            $query
                                ->where('is_variation', 0)
                                ->where(function ($query) use ($keyword): void {
                                    $query
                                        ->orWhere('ec_products.sku', 'LIKE', $keyword)
                                        ->orWhere('ec_products.created_at', 'LIKE', $keyword)
                                        ->when(
                                            in_array('sku', EcommerceHelper::getProductsSearchBy()),
                                            function ($query) use ($keyword): void {
                                                $query
                                                    ->orWhereHas(
                                                        'variations.product',
                                                        function ($query) use ($keyword): void {
                                                            $query->where('sku', 'LIKE', $keyword);
                                                        }
                                                    );
                                            }
                                        );
                                });
                        });

                    return $query;
                }

                return $query;
            });

        return $this->toJson($data);
    }

    public function htmlDrawCallbackFunction(): ?string
    {
        return parent::htmlDrawCallbackFunction() . 'Botble.initEditable()';
    }

    public function buttons(): array
    {
        $buttons = [];

        if (EcommerceHelper::isEnabledSupportDigitalProducts() && ! EcommerceHelper::isDisabledPhysicalProduct() && $this->hasPermission('products.create')) {
            $buttons['create'] = [
                'extend' => 'collection',
                'text' => view('core/table::partials.create')->render(),
                'class' => 'btn-primary',
                'buttons' => [
                    [
                        'className' => 'action-item',
                        'text' => ProductTypeEnum::PHYSICAL()->toIcon() . ' ' . Html::tag(
                            'span',
                            ProductTypeEnum::PHYSICAL()->label(),
                            [
                                'data-action' => 'physical-product',
                                'data-href' => route('products.create'),
                                'class' => 'ms-1',
                            ]
                        )->toHtml(),
                    ],
                    [
                        'className' => 'action-item',
                        'text' => ProductTypeEnum::DIGITAL()->toIcon() . ' ' . Html::tag(
                            'span',
                            ProductTypeEnum::DIGITAL()->label(),
                            [
                                'data-action' => 'digital-product',
                                'data-href' => route('products.create', ['product_type' => 'digital']),
                                'class' => 'ms-1',
                            ]
                        )->toHtml(),
                    ],
                ],
            ];
        } elseif (! EcommerceHelper::isEnabledSupportDigitalProducts() || EcommerceHelper::isDisabledPhysicalProduct()) {
            $buttons = $this->addCreateButton(route('products.create'), 'products.create');
        }

        return $buttons;
    }

    public function renderTable($data = [], $mergeData = []): View|Factory|Response
    {
        if ($this->isEmpty()) {
            return view('plugins/ecommerce::products.intro');
        }

        return parent::renderTable($data, $mergeData);
    }

    public function getFilters(): array
    {
        $data = parent::getFilters();

        $data['category'] = array_merge($data['category'], [
            'type' => 'select-ajax',
        ]);

        $data['brand_id'] = array_merge($data['brand_id'], [
            'type' => 'select-ajax',
        ]);

        $data['stock_status'] = [
            'title' => trans('plugins/ecommerce::products.form.stock_status'),
            'type' => 'select',
            'choices' => StockStatusEnum::labels(),
            'validate' => 'required|in:' . implode(',', StockStatusEnum::values()),
        ];

        $data['product_type'] = [
            'title' => trans('plugins/ecommerce::products.form.product_type.title'),
            'type' => 'select',
            'choices' => ProductTypeEnum::labels(),
            'validate' => 'required|in:' . implode(',', ProductTypeEnum::values()),
        ];

        $data['sku'] = [
            'title' => trans('plugins/ecommerce::products.sku'),
            'type' => 'text',
        ];

        return $data;
    }

    public function getBulkChanges(): array
    {
        return [
            NameBulkChange::make(),
            NumberBulkChange::make()
                ->name('order')
                ->title(trans('plugins/ecommerce::ecommerce.sort_order')),
            'category' => [
                'title' => trans('plugins/ecommerce::products.category'),
                'type' => 'select-ajax',
                'validate' => 'required',
                'callback' => function (int|string|null $value = null): array {
                    $categorySelected = [];
                    if ($value && $category = ProductCategory::query()->find($value)) {
                        $categorySelected = [$category->getKey() => $category->name];
                    }

                    return [
                        'url' => route('product-categories.search'),
                        'selected' => $categorySelected,
                        'minimum-input' => 1,
                    ];
                },
            ],
            'brand_id' => [
                'title' => trans('plugins/ecommerce::products.brand'),
                'type' => 'select-ajax',
                'validate' => 'required',
                'callback' => function (int|string|null $value = null): array {
                    $brandSelected = [];
                    if ($value && $brand = Brand::query()->find($value)) {
                        $brandSelected = [$brand->getKey() => $brand->name];
                    }

                    return [
                        'url' => route('brands.search'),
                        'selected' => $brandSelected,
                        'minimum-input' => 1,
                    ];
                },
            ],
            StatusBulkChange::make(),
            CreatedAtBulkChange::make(),
            IsFeaturedBulkChange::make(),
        ];
    }

    public function applyFilterCondition(
        EloquentBuilder|QueryBuilder|EloquentRelation $query,
        string $key,
        string $operator,
        ?string $value
    ): EloquentRelation|EloquentBuilder|QueryBuilder {
        switch ($key) {
            case 'created_at':
                if (! $value) {
                    break;
                }

                $value = BaseHelper::formatDate($value);

                return $query->whereDate('ec_products.' . $key, $operator, $value);
            case 'category':
                if (! $value) {
                    break;
                }

                if (! BaseHelper::isJoined($query, 'ec_product_categories')) {
                    $query = $query
                        ->join(
                            'ec_product_category_product',
                            'ec_product_category_product.product_id',
                            '=',
                            'ec_products.id'
                        )
                        ->join(
                            'ec_product_categories',
                            'ec_product_category_product.category_id',
                            '=',
                            'ec_product_categories.id'
                        )
                        ->select($query->getModel()->getTable() . '.*');
                }

                return $query->where('ec_product_category_product.category_id', $value);

            case 'brand':
                if (! $value) {
                    break;
                }

                return $query->where('ec_products.brand_id', $operator, $value);

            case 'stock_status':
                if (! $value) {
                    break;
                }

                if ($value == StockStatusEnum::ON_BACKORDER) {
                    return parent::applyFilterCondition($query, $key, $operator, $value);
                }

                if ($value == StockStatusEnum::OUT_OF_STOCK) {
                    return $query
                        ->where(function ($query): void {
                            $query
                                ->where(function ($subQuery): void {
                                    $subQuery
                                        ->where('with_storehouse_management', 0)
                                        ->where('stock_status', StockStatusEnum::OUT_OF_STOCK);
                                })
                                ->orWhere(function ($subQuery): void {
                                    $subQuery
                                        ->where('with_storehouse_management', 1)
                                        ->where('allow_checkout_when_out_of_stock', 0)
                                        ->where('quantity', '<=', 0);
                                });
                        });
                }

                if ($value == StockStatusEnum::IN_STOCK) {
                    return $query
                        ->where(function ($query) {
                            return $query
                                ->where(function ($subQuery): void {
                                    $subQuery
                                        ->where('with_storehouse_management', 0)
                                        ->where('stock_status', StockStatusEnum::IN_STOCK);
                                })
                                ->orWhere(function ($subQuery): void {
                                    $subQuery
                                        ->where('with_storehouse_management', 1)
                                        ->where(function ($sub): void {
                                            $sub
                                                ->where('allow_checkout_when_out_of_stock', 1)
                                                ->orWhere('quantity', '>', 0);
                                        });
                                });
                        });
                }
        }

        return parent::applyFilterCondition($query, $key, $operator, $value);
    }

    public function saveBulkChangeItem(Model|Product $item, string $inputKey, ?string $inputValue): Model|bool
    {
        if ($inputKey === 'category') {
            /**
             * @var Product $item
             */
            $item->categories()->sync([$inputValue]);

            return $item;
        }

        return parent::saveBulkChangeItem($item, $inputKey, $inputValue);
    }
}
