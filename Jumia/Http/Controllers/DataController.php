<?php

namespace Modules\Jumia\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Menu;

class DataController extends Controller
{
    public function dummy_data()
    {
        Artisan::call('db:seed', ["--class" => 'Modules\Jumia\Database\Seeders\AddDummySyncLogTableSeeder']);
    }

    public function superadmin_package()
    {
        return [
            [
                'name' => 'jumia_module',
                'label' => __('jumia::lang.jumia_module'),
                'default' => false
            ]
        ];
    }

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'jumia.syc_categories',
                'label' => __('jumia::lang.sync_product_categories'),
                'default' => false
            ],
            [
                'value' => 'jumia.sync_products',
                'label' => __('jumia::lang.sync_products'),
                'default' => false
            ],
            [
                'value' => 'jumia.sync_orders',
                'label' => __('jumia::lang.sync_orders'),
                'default' => false
            ],
          
            [
                'value' => 'jumia.access_jumia_api_settings',
                'label' => __('jumia::lang.access_jumia_api_settings'),
                'default' => false
            ],

        ];
    }

    /**
     * Parses notification message from database.
     * @return array
     */
    public function parse_notification($notification)
    {
        $notification_data = [];
        if ($notification->type ==
            'Modules\Jumia\Notifications\SyncOrdersNotification') {
            $msg = __('jumia::lang.orders_sync_notification');

            $notification_data = [
                'msg' => $msg,
                'icon_class' => "fas fa-sync bg-light-blue",
                'link' =>  action('SellController@index'),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        }

        return $notification_data;
    }

    /**
     * Returns product form part path with required extra data.
     *
     * @return array
     */
    public function product_form_part()
    {
        $path = 'jumia::jumia.partials.product_form_part';

        $business_id = request()->session()->get('user.business_id');

        $module_util = new ModuleUtil();
        $is_woo_enabled = (boolean)$module_util->hasThePermissionInSubscription($business_id, 'jumia_module', 'superadmin_package');
        if ($is_woo_enabled) {
            return  [
                'template_path' => $path,
                'template_data' => []
            ];
        } else {
            return [];
        }
    }

    /**
     * Returns products table extra columns for this module
     *
     * @return array
     */
    public function product_form_fields()
    {
        return ['jumia_disable_sync'];
    }

    /**
     * Adds jumia menus
     * @return null
     */
    public function modifyAdminMenu()
    {
        $module_util = new ModuleUtil();
        
        $business_id = session()->get('user.business_id');
        $is_woo_enabled = (boolean)$module_util->hasThePermissionInSubscription($business_id, 'jumia_module', 'superadmin_package');

        if ($is_woo_enabled && (auth()->user()->can('jumia.syc_categories') || auth()->user()->can('jumia.sync_products') || auth()->user()->can('jumia.sync_orders') || auth()->user()->can('jumia.map_tax_rates') || auth()->user()->can('jumia.access_jumia_api_settings'))) {
            Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(
                    action('\Modules\Jumia\Http\Controllers\JumiaController@index'),
                    __('jumia::lang.jumia'),
                    ['icon' => 'fab fa-opencart', 'style' => config('app.env') == 'demo' ? 'background-color: #9E458B !important;' : '', 'active' => request()->segment(1) == 'jumia']
                )->order(21);
            });
        }
    }
}
