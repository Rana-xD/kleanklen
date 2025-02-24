<?php

namespace Botble\Dashboard\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Dashboard\Events\RenderingDashboardWidgets;
use Botble\Dashboard\Models\DashboardWidget;
use Botble\Dashboard\Models\DashboardWidgetSetting;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * Controller for handling dashboard-related operations
 * 
 * This controller manages the admin dashboard including:
 * - Widget display and organization
 * - Widget settings and preferences
 * - Dashboard layout customization
 */
class DashboardController extends BaseController
{
    /**
     * Display the admin dashboard
     * 
     * Loads and displays dashboard widgets, statistics, and user-specific settings
     * Handles widget organization and visibility based on user preferences
     * 
     * @param Request $request HTTP request instance
     * @return \Illuminate\View\View Dashboard view with widgets
     */
    public function getDashboard(Request $request)
    {
        $this->pageTitle(trans('core/dashboard::dashboard.title'));

        Assets::addScripts(['sortable', 'equal-height', 'counterup'])
            ->addScriptsDirectly('vendor/core/core/dashboard/js/dashboard.js')
            ->addScriptsDirectly('vendor/core/core/dashboard/js/check-for-updates.js');

        Assets::usingVueJS();

        RenderingDashboardWidgets::dispatch();

        do_action(DASHBOARD_ACTION_REGISTER_SCRIPTS);

        $widgets = DashboardWidget::query()
            ->with([
                'settings' => function (HasMany $query) use ($request): void {
                    $query
                        ->where('user_id', $request->user()->getKey())
                        ->select(['status', 'order', 'settings', 'widget_id'])
                        ->oldest('order');
                },
            ])
            ->select(['id', 'name'])
            ->get();

        $widgetData = apply_filters(DASHBOARD_FILTER_ADMIN_LIST, [], $widgets);

        $availableWidgetIds = collect($widgetData)->pluck('id')->all();

        $widgets = $widgets->reject(function ($item) use ($availableWidgetIds) {
            return ! in_array($item->getKey(), $availableWidgetIds);
        });

        $statWidgets = collect($widgetData)
            ->where('type', '!=', 'widget')
            ->sortBy('priority')
            ->pluck('view')
            ->all();

        $userWidgets = collect($widgetData)
            ->where('type', 'widget')
            ->sortBy('priority')
            ->pluck('view')
            ->all();

        return view('core/dashboard::list', compact('widgets', 'userWidgets', 'statWidgets'));
    }

    /**
     * Update settings for a specific dashboard widget
     * 
     * @param Request $request HTTP request with widget settings
     * @return \Illuminate\Http\JsonResponse Response indicating success/failure
     */
    public function postEditWidgetSettingItem(Request $request)
    {
        try {
            $widget = DashboardWidget::query()->where([
                'name' => $request->input('name'),
            ])->first();

            if (! $widget) {
                return $this
                    ->httpResponse()
                    ->setError()
                    ->setMessage(trans('core/dashboard::dashboard.widget_not_exists'));
            }

            $widgetSetting = DashboardWidgetSetting::query()->create([
                'widget_id' => $widget->getKey(),
                'user_id' => $request->user()->getKey(),
            ]);

            $widgetSetting->settings = array_merge((array) $widgetSetting->settings, [
                $request->input('setting_name') => $request->input('setting_value'),
            ]);

            $widgetSetting->save();
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }

        return $this->httpResponse();
    }

    /**
     * Update the display order of dashboard widgets
     * 
     * @param Request $request HTTP request with widget order data
     * @return \Illuminate\Http\JsonResponse Response with success message
     */
    public function postUpdateWidgetOrder(Request $request)
    {
        foreach ($request->input('items', []) as $key => $item) {
            $widget = DashboardWidget::query()->firstOrCreate([
                'name' => $item,
            ]);

            $widgetSetting = DashboardWidgetSetting::query()->firstOrCreate([
                'widget_id' => $widget->getKey(),
                'user_id' => $request->user()->getKey(),
            ]);

            $widgetSetting->order = $key;
            $widgetSetting->save();
        }

        return $this
            ->httpResponse()
            ->setMessage(trans('core/dashboard::dashboard.update_position_success'));
    }

    /**
     * Hide a specific widget from the dashboard
     * 
     * @param Request $request HTTP request with widget name
     * @return \Illuminate\Http\JsonResponse Response with success message
     */
    public function getHideWidget(Request $request)
    {
        $widget = DashboardWidget::query()->where([
            'name' => $request->input('name'),
        ], ['id'])->first();

        if (! empty($widget)) {
            $widgetSetting = DashboardWidgetSetting::query()->firstOrCreate([
                'widget_id' => $widget->getKey(),
                'user_id' => $request->user()->getKey(),
            ]);

            $maxOrder = DashboardWidgetSetting::query()->max('order');

            $maxOrder = $maxOrder < 125 ? $maxOrder : 10;

            $widgetSetting->status = 0;
            $widgetSetting->order = $maxOrder + 1;
            $widgetSetting->save();
        }

        return $this
            ->httpResponse()
            ->setMessage(trans('core/dashboard::dashboard.hide_success'));
    }

    /**
     * Bulk update visibility settings for multiple widgets
     * 
     * @param Request $request HTTP request with widget visibility data
     * @return \Illuminate\Http\JsonResponse Response with success message
     */
    public function postHideWidgets(Request $request)
    {
        $widgets = DashboardWidget::query()->get();

        foreach ($widgets as $widget) {
            $widgetSetting = DashboardWidgetSetting::query()->firstOrCreate([
                'widget_id' => $widget->getKey(),
                'user_id' => $request->user()->getKey(),
            ]);

            if (
                $request->has('widgets.' . $widget->name) &&
                $request->input('widgets.' . $widget->name) == 1
            ) {
                $widgetSetting->status = 1;
            } else {
                $maxOrder = DashboardWidgetSetting::query()->max('order');

                $maxOrder = $maxOrder < 125 ? $maxOrder : 10;

                $widgetSetting->status = 0;
                $widgetSetting->order = $maxOrder + 1;
            }

            $widgetSetting->save();
        }

        return $this
            ->httpResponse()
            ->setNextRoute('dashboard.index')
            ->setMessage(trans('core/dashboard::dashboard.hide_success'));
    }
}
