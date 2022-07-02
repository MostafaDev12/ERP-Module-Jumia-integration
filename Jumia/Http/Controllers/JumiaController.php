<?php

namespace Modules\Jumia\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Media;
use App\Product;
use App\SellingPriceGroup;
use App\System;
use App\TaxRate;
use App\Utils\ModuleUtil;
use App\Variation;
use App\VariationTemplate;
use DB;
use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\Routing\Controller;
use Modules\Jumia\Entities\JumiaSyncLog;

use Modules\Jumia\Utils\JumiaUtil;

use Yajra\DataTables\Facades\DataTables;
use Modules\Jumia\Http\traits\ApiTrait;

class JumiaController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    use ApiTrait; 
    protected $jumiaUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param jumiaUtil $jumiaUtil
     * @return void
     */
    public function __construct(JumiaUtil $jumiaUtil, ModuleUtil $moduleUtil)
    {
        $this->jumiaUtil = $jumiaUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        try {
            $business_id = request()->session()->get('business.id');
  
             
          if (!(auth()->user()->can('superadmin') ||  $this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module')  )) {
            abort(403, 'Unauthorized action.');
        }

                            
          

            $jumia_api_settings = $this->jumiaUtil->get_api_settings($business_id);
     
            $alerts = [];

   
            $products_last_synced = $this->jumiaUtil->getLastSync($business_id, 'all_products', false);
            $query = Product::where('business_id', $business_id)
                                        ->whereIn('type', ['single', 'variable'])
                                        ->whereNull('jumia_product_id')
                                        ->where('jumia_disable_sync', 0);

            if (!empty($jumia_api_settings->location_id)) {
                $query->ForLocation($jumia_api_settings->location_id);
            }
            $not_synced_product_count = $query->count();

            if (!empty($not_synced_product_count)) {
                $alerts['not_synced_product'] = $not_synced_product_count == 1 ? __('jumia::lang.one_product_not_sync_alert') : __('jumia::lang.product_not_sync_alert', ['count' => $not_synced_product_count]);
            }
            if (!empty($products_last_synced)) {
                $updated_product_count = Product::where('business_id', $business_id)
                                        ->whereNotNull('jumia_product_id')
                                        ->where('jumia_disable_sync', 0)
                                        ->whereIn('type', ['single', 'variable'])
                                        ->where('updated_at', '>', $products_last_synced)
                                        ->count();
            }

            if (!empty($updated_product_count)) {
                $alerts['not_updated_product'] = $updated_product_count == 1 ? __('jumia::lang.one_product_updated_alert') : __('jumia::lang.product_updated_alert', ['count' => $updated_product_count]);
            }

          
      } catch (\Exception $e) {
            $alerts['connection_failed'] = 'Unable to connect with jumia, Check API settings';
        }
        

        return view('jumia::jumia.index')
                ->with(compact('alerts'));
    }

    /**
     * Displays form to update jumia api settings.
     * @return Response
     */
    public function apiSettings()
    {
        $business_id = request()->session()->get('business.id');

        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module') && auth()->user()->can('jumia.access_jumia_api_settings')))) {
            abort(403, 'Unauthorized action.');
        }

        $default_settings = [
            'jumia_app_url' => '',
            'jumia_consumer_key' => '',
            'jumia_consumer_secret' => '',
            'location_id' => null,
            'default_tax_class' => '',
            'product_tax_type' => 'inc',
            'default_selling_price_group' => '',
            'product_fields_for_create' => ['category', 'quantity','weight', 'description', 'image'],
            'product_fields_for_update' => ['name', 'price', 'category', 'quantity'],
        ];

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
                        ->pluck('name', 'id')->prepend(__('lang_v1.default'), '');

        $business = Business::find($business_id);

        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            $business = null;
        }

        if (!empty($business->jumia_api_settings)) {
            $default_settings = json_decode($business->jumia_api_settings, true);
            if (empty($default_settings['product_fields_for_create'])) {
                $default_settings['product_fields_for_create'] = [];
            }

            if (empty($default_settings['product_fields_for_update'])) {
                $default_settings['product_fields_for_update'] = [];
            }
        }

        $locations = BusinessLocation::forDropdown($business_id);
        $module_version = System::getProperty('jumia_version');

        $cron_job_command = $this->moduleUtil->getCronJobCommand();

        $shipping_statuses = $this->moduleUtil->shipping_statuses();

        return view('jumia::jumia.api_settings')
                ->with(compact('default_settings', 'locations', 'price_groups', 'module_version', 'cron_job_command', 'business', 'shipping_statuses'));
    }

    /**
     * Updates jumia api settings.
     * @return Response
     */
    public function updateSettings(Request $request)
    {
        $business_id = request()->session()->get('business.id');

        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module') && auth()->user()->can('jumia.access_jumia_api_settings')))) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $input = $request->except('_token');

            $input['product_fields_for_create'] = !empty($input['product_fields_for_create']) ? $input['product_fields_for_create'] : [];
            $input['product_fields_for_update'] = !empty($input['product_fields_for_update']) ? $input['product_fields_for_update'] : [];
            $input['order_statuses'] = !empty($input['order_statuses']) ? $input['order_statuses'] : [];
            $input['shipping_statuses'] = !empty($input['shipping_statuses']) ? $input['shipping_statuses'] : [];

            $business = Business::find($business_id);
            $business->jumia_api_settings = json_encode($input);
     /*       $business->jumia_wh_oc_secret = $input['jumia_wh_oc_secret'];
            $business->jumia_wh_ou_secret = $input['jumia_wh_ou_secret'];
            $business->jumia_wh_od_secret = $input['jumia_wh_od_secret'];
            $business->jumia_wh_or_secret = $input['jumia_wh_or_secret'];*/
            $business->save();

            $output = ['success' => 1,
                            'msg' => trans("lang_v1.updated_succesfully")
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => trans("messages.something_went_wrong")
                        ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Synchronizes pos categories with jumia categories
     * @return Response
     */
    public function syncStocks()
    {
        $business_id = request()->session()->get('business.id');

        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module') && auth()->user()->can('jumia.syc_categories')))) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }
           ini_set('memory_limit', '-1');
           ini_set('max_execution_time', 0);

        try {
            $user_id = request()->session()->get('user.id');
            $sync_type = request()->input('type');

            DB::beginTransaction();

            $offset = request()->input('offset');
            $limit = 1000000;
            $all_products = $this->jumiaUtil->syncStocks($business_id, $user_id, $sync_type, $limit, $offset);
            $total_products = count($all_products);
            
            DB::commit();
            $msg = $total_products > 0 ?  __("jumia::lang.n_products_synced_successfully", ['count' => $total_products]) :  __("jumia::lang.synced_successfully");
            $output = ['success' => 1,
                            'msg' => $msg,
                            'total_products' => $total_products
                        ];
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == 'Modules\jumia\Exceptions\jumiaError') {
                $output = ['success' => 0,
                            'msg' => $e->getMessage(),
                        ];
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }
        }

        return $output;
    }

    /**
     * Synchronizes pos products with jumia products
     * @return Response
     */
     
     
     
    public function syncProducts()
    {
        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module') && auth()->user()->can('jumia.sync_products')))) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        try {
            $user_id = request()->session()->get('user.id');
            $sync_type = request()->input('type');

            DB::beginTransaction();

            $offset = request()->input('offset');
            $limit = 1000000;
            $all_products = $this->jumiaUtil->syncProducts($business_id, $user_id, $sync_type, $limit, $offset);
            $total_products = count($all_products);
            
            DB::commit();
            $msg = $total_products > 0 ?  __("jumia::lang.n_products_synced_successfully", ['count' => $total_products]) :  __("jumia::lang.synced_successfully");
            $output = ['success' => 1,
                            'msg' => $msg,
                            'total_products' => $total_products
                        ];
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == 'Modules\jumia\Exceptions\jumiaError') {
                $output = ['success' => 0,
                            'msg' => $e->getMessage(),
                        ];
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }
        }
        
        return $output;
    }



    /**
     * Synchronizes Woocommers Orders with POS sales
     * @return Response
     */
    public function syncOrders()
    {
        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module') && auth()->user()->can('jumia.sync_orders')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            $user_id = request()->session()->get('user.id');
           
            $this->jumiaUtil->syncOrders($business_id, $user_id);

            DB::commit();

            $output = ['success' => 1,
                            'msg' => __("jumia::lang.synced_successfully")
                        ];
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == 'Modules\jumia\Exceptions\jumiaError') {
                $output = ['success' => 0,
                            'msg' => $e->getMessage(),
                        ];
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong"),
                        ];
            }
        }

        return $output;
    }

    /**
     * Retrives sync log
     * @return Response
     */
    public function getSyncLog()
    {
        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $last_sync = [
                'categories' => $this->jumiaUtil->getLastSync($business_id, 'categories'),
                'new_products' => $this->jumiaUtil->getLastSync($business_id, 'new_products'),
                'update_products' => $this->jumiaUtil->getLastSync($business_id, 'update_products'),
                'all_products' => $this->jumiaUtil->getLastSync($business_id, 'all_products'),
                'orders' => $this->jumiaUtil->getLastSync($business_id, 'orders')

            ];
            return $last_sync;
        }
    }

    /**
     * Maps POS tax_rates with jumia tax rates.
     * @return Response
     */
    public function mapTaxRates(Request $request)
    {
        $notAllowed = $this->jumiaUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module') && auth()->user()->can('jumia.map_tax_rates')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');
            foreach ($input['taxes'] as $key => $value) {
                $value = !empty($value) ? $value : null;
                TaxRate::where('business_id', $business_id)
                        ->where('id', $key)
                        ->update(['jumia_tax_rate_id' => $value]);
            }

            $output = ['success' => 1,
                            'msg' => __("lang_v1.updated_succesfully")
                        ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                        'msg' => __("messages.something_went_wrong"),
                    ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function viewSyncLog()
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $logs = JumiaSyncLog::where('jumia_sync_logs.business_id', $business_id)
                    ->leftjoin('users as U', 'U.id', '=', 'jumia_sync_logs.created_by')
                    ->select([
                        'jumia_sync_logs.created_at',
                        'sync_type', 'operation_type',
                        DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                        'jumia_sync_logs.data',
                        'jumia_sync_logs.details as log_details',
                        'jumia_sync_logs.id as DT_RowId'
                    ]);
            $sync_type = [];
            if (auth()->user()->can('jumia.syc_categories')) {
                $sync_type[] = 'categories';
            }
            if (auth()->user()->can('jumia.sync_products')) {
                $sync_type[] = 'all_products';
                $sync_type[] = 'new_products';
                $sync_type[] = 'update_products';
            }

            if (auth()->user()->can('jumia.sync_orders')) {
                $sync_type[] = 'orders';
            }
            if (!auth()->user()->can('superadmin')) {
                $logs->whereIn('sync_type', $sync_type);
            }

            return Datatables::of($logs)
                ->editColumn('created_at', function ($row) {
                    $created_at = $this->jumiaUtil->format_date($row->created_at, true);
                    $for_humans = \Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)->diffForHumans();
                    return $created_at . '<br><small>' . $for_humans . '</small>';
                })
                ->editColumn('sync_type', function ($row) {
                    $array = [
                        'categories' => __('category.categories'),
                        'all_products' => __('sale.products'),
                        'new_products' => __('sale.products'),
                        'update_products' => __('sale.products'),
                        'orders' => __('jumia::lang.orders'),
                    ];
                    return $array[$row->sync_type];
                })
                ->editColumn('operation_type', function ($row) {
                    $array = [
                        'created' => __('jumia::lang.created'),
                        'updated' => __('jumia::lang.updated'),
                        'reset' => __('jumia::lang.reset'),
                        'deleted' => __('lang_v1.deleted'),
                        'restored' => __('jumia::lang.order_restored')
                    ];
                    return array_key_exists($row->operation_type, $array) ? $array[$row->operation_type] : '';
                })
                ->editColumn('data', function ($row) {
                    if (!empty($row->data)) {
                        $data = json_decode($row->data, true);
                        return implode(', ', $data) . '<br><small>' . count($data) . ' ' . __('jumia::lang.records') . '</small>';
                    } else {
                        return '';
                    }
                })
                ->editColumn('log_details', function ($row) {
                    $details = '';
                    if (!empty($row->log_details)) {
                        $details = $row->log_details;
                    }
                    return $details;
                })
                ->filterColumn('full_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['created_at', 'data'])
                ->make(true);
        }


  
  //    $jumia = $this->product_update($business_id,['Action' => 'ProductUpdate']);
// dd($jumia->SuccessResponse);

        return view('jumia::jumia.sync_log');
    }

    /**
     * Retrives details of a sync log.
     * @param int $id
     * @return Response
     */
    public function getLogDetails($id)
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $log = JumiaSyncLog::where('business_id', $business_id)
                                            ->find($id);
            $log_details = json_decode($log->details);
            
            return view('jumia::jumia.partials.log_details')
                    ->with(compact('log_details'));
        }
    }

    /**
     * Resets synced categories
     * @return json
     */
    public function resetCategories()
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                Category::where('business_id', $business_id)
                        ->update(['jumia_cat_id' => null]);
                $user_id = request()->session()->get('user.id');
                $this->jumiaUtil->createSyncLog($business_id, $user_id, 'categories', 'reset', null);

                $output = ['success' => 1,
                            'msg' => __("jumia::lang.cat_reset_success"),
                        ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong"),
                        ];
            }

            return $output;
        }
    }

    /**
     * Resets synced products
     * @return json
     */
    public function resetProducts()
    {
        $business_id = request()->session()->get('business.id');
        if (!(auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'jumia_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                //Update products table
                Product::where('business_id', $business_id)
                        ->update(['jumia_product_id' => null, 'jumia_media_id' => null]);

                $product_ids = Product::where('business_id', $business_id)
                                    ->pluck('id');

                $product_ids = !empty($product_ids) ? $product_ids : [];
                //Update variations table
                Variation::whereIn('product_id', $product_ids)
                        ->update([
                            'jumia_variation_id' => null
                        ]);

                //Update variation templates
                VariationTemplate::where('business_id', $business_id)
                                ->update([
                                    'jumia_attr_id' => null
                                ]);

                Media::where('business_id', $business_id)
                        ->update(['jumia_media_id' => null]);

                $user_id = request()->session()->get('user.id');
                $this->jumiaUtil->createSyncLog($business_id, $user_id, 'all_products', 'reset', null);

                $output = ['success' => 1,
                            'msg' => __("jumia::lang.prod_reset_success"),
                        ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => 0,
                            'msg' => "File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage(),
                        ];
            }

            return $output;
        }
    }
}
