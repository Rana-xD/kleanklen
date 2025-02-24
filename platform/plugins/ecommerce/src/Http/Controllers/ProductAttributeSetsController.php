<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Supports\Breadcrumb;
use Botble\Ecommerce\Forms\ProductAttributeSetForm;
use Botble\Ecommerce\Http\Requests\ProductAttributeSetsRequest;
use Botble\Ecommerce\Models\ProductAttributeSet;
use Botble\Ecommerce\Services\ProductAttributes\StoreAttributeSetService;
use Botble\Ecommerce\Tables\ProductAttributeSetsTable;

/**
 * Class ProductAttributeSetsController
 * 
 * @package Botble\Ecommerce\Http\Controllers
 * 
 * Controller for managing product attribute sets in the e-commerce system.
 * Handles CRUD operations for product attributes like size, color, material etc.
 */
class ProductAttributeSetsController extends BaseController
{
    /**
     * Configure the breadcrumb for the controller
     *
     * @return Breadcrumb
     */
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/ecommerce::product-attributes.name'), route('product-attribute-sets.index'));
    }

    /**
     * Display a listing of product attribute sets
     *
     * @param ProductAttributeSetsTable $dataTable
     * @return mixed
     */
    public function index(ProductAttributeSetsTable $dataTable)
    {
        $this->pageTitle(trans('plugins/ecommerce::product-attributes.name'));

        return $dataTable->renderTable();
    }

    /**
     * Show the form for creating a new product attribute set
     *
     * @return string
     */
    public function create()
    {
        $this->pageTitle(trans('plugins/ecommerce::product-attributes.create'));

        return ProductAttributeSetForm::create()->renderForm();
    }

    /**
     * Store a newly created product attribute set
     *
     * @param ProductAttributeSetsRequest $request
     * @param StoreAttributeSetService $service
     * @return mixed
     */
    public function store(ProductAttributeSetsRequest $request, StoreAttributeSetService $service)
    {
        $productAttributeSet = $service->execute($request, new ProductAttributeSet());

        $productAttributeSet->categories()->detach();

        $productAttributeSet->categories()->sync((array) $request->input('categories', []));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('product-attribute-sets.index'))
            ->setNextUrl(route('product-attribute-sets.edit', $productAttributeSet->getKey()))
            ->withCreatedSuccessMessage();
    }

    /**
     * Show the form for editing the specified product attribute set
     *
     * @param ProductAttributeSet $productAttributeSet
     * @return string
     */
    public function edit(ProductAttributeSet $productAttributeSet)
    {
        $this->pageTitle(trans('plugins/ecommerce::product-attributes.edit'));

        return ProductAttributeSetForm::createFromModel($productAttributeSet)
            ->renderForm();
    }

    /**
     * Update the specified product attribute set
     *
     * @param ProductAttributeSet $productAttributeSet
     * @param ProductAttributeSetsRequest $request
     * @param StoreAttributeSetService $service
     * @return mixed
     */
    public function update(
        ProductAttributeSet $productAttributeSet,
        ProductAttributeSetsRequest $request,
        StoreAttributeSetService $service,
    ) {
        $service->execute($request, $productAttributeSet);

        $productAttributeSet->categories()->detach();

        $productAttributeSet->categories()->sync((array) $request->input('categories', []));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('product-attribute-sets.index'))
            ->withUpdatedSuccessMessage();
    }

    /**
     * Remove the specified product attribute set
     *
     * @param ProductAttributeSet $productAttributeSet
     * @return mixed
     */
    public function destroy(ProductAttributeSet $productAttributeSet)
    {
        return DeleteResourceAction::make($productAttributeSet);
    }
}
