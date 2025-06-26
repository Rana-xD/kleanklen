<?php

namespace Botble\Ecommerce\Tables;

use Botble\Ecommerce\Models\Product;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\ImageColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

abstract class ProductBulkEditableTable extends TableAbstract
{
    protected int $pageLength = 100;

    protected array $productIds = [];

    protected bool $hasResponsive = false;

    public function hasColumnVisibilityEnabled(): bool
    {
        return false;
    }

    public function setup(): void
    {
        $this
            ->addColumns([
                Column::make('DT_RowIndex')
                    ->title(trans('core/base::tables.id'))
                    ->alignStart()
                    ->orderable(false)
                    ->searchable(false),
                ImageColumn::make()
                    ->orderable(false)
                    ->renderUsing(function (ImageColumn $column, $content) {
                        $images = $column->getItem()->images;
                        $images = $images ? json_decode($images, true) : [];
                        $firstImage = Arr::first($images) ?: null;

                        if ($column->getItem()->is_variation) {
                            return '';
                        }

                        $content = $content ?: $firstImage;

                        return $column->formattedValue($content);
                    }),
                FormattedColumn::make('name')
                    ->title(trans('plugins/ecommerce::products.name'))
                    ->renderUsing(function (FormattedColumn $column) {
                        return view('plugins/ecommerce::product-prices.columns.name', [
                            'product' => $column->getItem(),
                        ]);
                    })
                    ->orderable(false),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->query($this->query())
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

    protected function hasOperations(): bool
    {
        return false;
    }

    public function query()
    {
        return $this->applyScopes(Product::getGroupedVariationQuery());
    }
}
