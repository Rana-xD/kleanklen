<?php

namespace Botble\Ecommerce\Tables;

use Botble\Table\Columns\Column;
use Botble\Table\Columns\FormattedColumn;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;

class ProductPriceTable extends ProductBulkEditableTable
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setView('plugins/ecommerce::product-prices.index')
            ->addColumns([
                FormattedColumn::make('cost_per_item')
                    ->title(trans('plugins/ecommerce::products.form.cost_per_item'))
                    ->renderUsing(function (FormattedColumn $column) {
                        return view('plugins/ecommerce::product-prices.columns.price', [
                            'product' => $column->getItem(),
                            'type' => 'cost_per_item',
                        ]);
                    })
                    ->nowrap()
                    ->width(150)
                    ->orderable(false),
                FormattedColumn::make('price')
                    ->title(trans('plugins/ecommerce::products.form.price'))
                    ->renderUsing(function (FormattedColumn $column) {
                        return view('plugins/ecommerce::product-prices.columns.price', [
                            'product' => $column->getItem(),
                            'type' => 'price',
                        ]);
                    })
                    ->nowrap()
                    ->width(150)
                    ->orderable(false),
                FormattedColumn::make('sale_price')
                    ->title(trans('plugins/ecommerce::products.form.price_sale'))
                    ->renderUsing(function (FormattedColumn $column) {
                        return view('plugins/ecommerce::product-prices.columns.price', [
                            'product' => $column->getItem(),
                            'type' => 'sale_price',
                        ]);
                    })
                    ->nowrap()
                    ->width(150)
                    ->orderable(false),
                FormattedColumn::make('with_storehouse_management')
                    ->title(trans('plugins/ecommerce::product-inventory.storehouse_management'))
                    ->renderUsing(function (FormattedColumn $column) {
                        return view('plugins/ecommerce::product-inventory.columns.storehouse_management', [
                            'product' => $column->getItem(),
                            'type' => 'storehouse_management',
                        ]);
                    })
                    ->nowrap()
                    ->orderable(false),
                FormattedColumn::make('quantity')
                    ->title(trans('plugins/ecommerce::products.form.quantity'))
                    ->renderUsing(function (FormattedColumn $column) {
                        return view('plugins/ecommerce::product-inventory.columns.quantity', [
                            'product' => $column->getItem(),
                        ]);
                    })
                    ->nowrap()
                    ->orderable(false),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->query($this->query())
            ->addIndexColumn() // Add sequential row numbering
            ->filter(function ($query) {
                $keyword = $this->request()->input('search.value');

                if ($keyword) {
                    $keyword = '%' . $keyword . '%';

                    $query
                        ->where('ec_products.name', 'LIKE', $keyword)
                        ->orWhere('ec_products.sku', 'LIKE', $keyword);

                    return $query;
                }

                return $query;
            });

        return $this->toJson($data);
    }

    public function query()
    {
        /**
         * @var Builder $query
         */
        $query = parent::query();

        $query->addSelect([
            'ec_products.cost_per_item',
            'ec_products.price',
            'ec_products.sale_price',
            'ec_products.sale_type',
            'ec_products.stock_status',
            'ec_products.quantity',
            'ec_products.with_storehouse_management',
        ]);

        return $query;
    }
}
