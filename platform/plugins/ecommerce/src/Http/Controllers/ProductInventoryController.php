<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Ecommerce\Http\Requests\UpdateProductInventoryRequest;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Services\Products\UpdateDefaultProductService;
use Botble\Ecommerce\Tables\ProductInventoryTable;

/**
 * Class ProductInventoryController
 *
 * @package Botble\Ecommerce\Http\Controllers
 *
 * Controller for managing product inventory in the e-commerce system.
 * Handles inventory listing and updates for products including stock management.
 */
class ProductInventoryController extends BaseController
{
    /**
     * Display a listing of product inventory
     *
     * @param ProductInventoryTable $dataTable The datatable instance for rendering inventory data
     * @return mixed Returns the rendered inventory table view
     */
    public function index(ProductInventoryTable $dataTable)
    {
        $this->pageTitle(trans('plugins/ecommerce::product-inventory.name'));

        Assets::addScriptsDirectly('vendor/core/plugins/ecommerce/js/product-bulk-editable-table.js');

        return $dataTable->renderTable();
    }

    /**
     * Update product inventory information
     *
     * @param Product $product The product model instance to update
     * @param UpdateProductInventoryRequest $request The validated request containing inventory data
     * @return mixed Returns the response with update status
     */
    public function update(Product $product, UpdateProductInventoryRequest $request)
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
