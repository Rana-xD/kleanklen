<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Ecommerce\Http\Requests\UpdateProductPriceRequest;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Services\Products\UpdateDefaultProductService;
use Botble\Ecommerce\Tables\ProductPriceTable;

/**
 * Class ProductPriceController
 *
 * @package Botble\Ecommerce\Http\Controllers
 *
 * Controller for managing product prices in the e-commerce system.
 * Handles price listing and updates for products including variation prices.
 */
class ProductPriceController extends BaseController
{
    /**
     * Display a listing of product prices
     *
     * @param ProductPriceTable $dataTable The datatable instance for rendering price data
     * @return mixed Returns the rendered price table view
     */
    public function index(ProductPriceTable $dataTable)
    {
        $this->pageTitle(trans('plugins/ecommerce::product-prices.name'));

        Assets::addScriptsDirectly('vendor/core/plugins/ecommerce/js/product-bulk-editable-table.js');

        return $dataTable->renderTable();
    }

    /**
     * Update product price information
     *
     * @param Product $product The product model instance to update
     * @param UpdateProductPriceRequest $request The validated request containing price data
     * @return mixed Returns the response with update status
     */
    public function update(Product $product, UpdateProductPriceRequest $request)
    {
        $product->forceFill([
            $request->input('column') => $request->input('value'),
        ])->save();

        if ($product->is_variation) {
            $product->load('variationInfo.configurableProduct');

            if ($product->variationInfo->is_default) {
                app(UpdateDefaultProductService::class)->execute($product);
            }
        }

        return $this->httpResponse()->withUpdatedSuccessMessage();
    }
}
