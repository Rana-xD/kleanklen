<?php

namespace Botble\Setting\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\Setting\Http\Controllers\Concerns\InteractsWithSettings;

/**
 * Abstract base controller for all setting-related controllers
 * 
 * This controller serves as the foundation for all setting management controllers
 * in the application. It provides:
 * - Common setting interaction functionality through InteractsWithSettings trait
 * - Consistent breadcrumb navigation for all setting pages
 * - Base structure for extending setting-specific controllers
 * 
 * All setting controllers should extend this class to maintain consistency
 * in handling settings throughout the application.
 */
abstract class SettingController extends BaseController
{
    use InteractsWithSettings;

    /**
     * Generate breadcrumb for setting pages
     * 
     * Adds the Settings entry to the breadcrumb navigation
     * 
     * @return Breadcrumb Returns the breadcrumb instance
     */
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('core/setting::setting.title'), route('settings.index'));
    }
}
