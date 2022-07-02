<?php
namespace Modules\Jumia\Utils;

use App\Business;
use App\Category;
use App\Contact;
use App\Address;
use App\Exceptions\PurchaseSellMismatch;
use App\Product;
use App\Variation;
use App\TaxRate;
use App\Transaction;
use App\Utils\ProductUtil;

use App\Utils\TransactionUtil;

use App\Utils\Util;
use App\Utils\ContactUtil;

use App\VariationLocationDetails;
use App\VariationTemplate;
//use Automattic\Jumia\Client;

use DB;
use Modules\Jumia\Entities\JumiaSyncLog;

use Modules\Jumia\Exceptions\jumiaError;
use Modules\Jumia\Http\traits\ApiTrait;

class JumiaUtil extends Util
{
    /**
     * All Utils instance.
     *
     */
     use ApiTrait; 
    protected $transactionUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }


    public function get_api_settings($business_id)
    {
        $business = Business::find($business_id);
        $jumia_api_settings = json_decode($business->jumia_api_settings);
        return $jumia_api_settings;
    }

    private function add_to_skipped_orders($business, $order_id)
    {
        $business = !is_object($business) ? Business::find($business) : $business;
        $skipped_orders = !empty($business->jumia_skipped_orders) ? json_decode($business->jumia_skipped_orders, true) : [];
        if (!in_array($order_id, $skipped_orders)) {
            $skipped_orders[] = $order_id;
        }

        $business->jumia_skipped_orders = json_encode($skipped_orders);
        $business->save();
    }

    private function remove_from_skipped_orders($business, $order_id)
    {
        $business = !is_object($business) ? Business::find($business) : $business;
        $skipped_orders = !empty($business->jumia_skipped_orders) ? json_decode($business->jumia_skipped_orders, true) : [];
        if (in_array($order_id, $skipped_orders)) {
            $skipped_orders = array_diff($skipped_orders, [$order_id]);
        }

        $business->jumia_skipped_orders = json_encode($skipped_orders);
        $business->save();
    }

    /**
     * Creates Automattic\jumia\Client object
     * @param int $business_id
     * @return obj
     */

    public function syncCat($business_id, $data, $type, $new_categories = [])
    {

        //jumia api client object
        $jumia = $this->woo_client($business_id);
        
    
        
        if($jumia){
            
          $count = 0;
          
          $request =  $type == 'create' ? 'POST'  : 'PUT' ;
          
        foreach ($data as $chunked_array) {
            $sync_data = [];
            $sync_data[$type] = $chunked_array;
            //Batch update categories
        if(empty($chunked_array['jumia_cat_id'])){
            
              $response = $this->curl_category($business_id,$chunked_array['name'],null ,$chunked_array['parent_id'],$request);   
            
        }else{
            
              $response = $this->curl_update_category($business_id,$chunked_array['name'] ,$chunked_array['jumia_cat_id'],$chunked_array['parent_id'],$request);    
            
        }
    
          //  $response = $jumia->post('products/categories/batch', $sync_data);
           $cat = Category::find($chunked_array['id']);
            //update jumia_cat_id
            if (!empty($response)) {
           
                  if (!empty($response->category_id)) {
                      
                      $cat->jumia_cat_id = $response->category_id;
                      
                      
                      
                   }
                   $cat->save(); 
                    $count++;
               
            }
        }    
            
            
            
            
        }
    
    }

    /**
     * Synchronizes pos categories with jumia categories
     * @param int $business_id
     * @return Void
     */
 
    /**
     * Synchronizes pos products with jumia products
     * @param int $business_id
     * @return Void
     */
    public function syncProducts($business_id, $user_id, $sync_type, $limit = 1000000, $page = 0)
    {
        //$limit is zero for console command
       if ($page == 0 || $limit == 0) {
             
            
            if ($limit > 0) {
                request()->session()->forget('last_product_synced');
            }
        } /**/

        $last_synced = !empty(session('last_product_synced')) ? session('last_product_synced') : $this->getLastSync($business_id, 'all_products', false);
        //store last_synced if page is 0
        if ($page == 0) {
            session(['last_product_synced' => $last_synced]);
        }/**/
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        $created_data = [];
        $updated_data = [];

        $business_location_id = $jumia_api_settings->location_id;
        $offset = $page * $limit;
        $query = Product::where('business_id', $business_id)
                        ->whereIn('type', ['single', 'variable'])
                        ->where('jumia_disable_sync', 0)
                        ->with(['variations', 'category', 'brand','variations.media', 'sub_category',
                            'variations.variation_location_details',
                            'variations.product_variation',
                            'variations.product_variation.variation_template']);

        if ($limit > 0) {
            $query->limit($limit)
                ->offset($offset);
        }/**/
                        
        if ($sync_type == 'new') {
            $query->whereNull('jumia_product_id');
        }
        if ($sync_type == 'update') {
            $query->whereNotNull('jumia_product_id');
        }

        //Select products only from selected location
        if (!empty($business_location_id)) {
            $query->ForLocation($business_location_id);
        }

        $all_products = $query->get();
        $product_data = [];
        $new_products = [];
        $updated_products = [];

        if (count($all_products) == 0) {
            request()->session()->forget('last_product_synced');
        }
            $create_response = [];
        $update_response = [];
        
        foreach ($all_products as $product) {
             $create_response = [];
        $update_response = [];
            //Skip product if last updated is less than last sync
            $last_updated = $product->updated_at;
            //check last stock updated
            $last_stock_updated = $this->getLastStockUpdated($business_location_id, $product->id);

            if (!empty($last_stock_updated)) {
                $last_updated = strtotime($last_stock_updated) > strtotime($last_updated) ?
                        $last_stock_updated : $last_updated;
            }
            if (!empty($product->jumia_product_id) && !empty($last_synced) && strtotime($last_updated) < strtotime($last_synced)) {
                continue;
            }

            //Get details from first variation for single product only
            $first_variation = $product->variations->first();
            if (empty($first_variation)) {
                continue;
            }

       
         $products = [];

            //Set common data
            $array = [
                'type' => $product->type == 'single' ? 'single' : 'variable',
                'sku' => $product->sku,
                'ParentSku' => $first_variation->sub_sku,
                'name' => $product->name,
            ];
            
            
            

            $manage_stock = false;
            if ($product->enable_stock == 1) {
                $manage_stock = true;
            }

         $qty_available = 0;   
         
         
         


            //Set product category
            $product_cat = [];
            if (!empty($product->category)) {
                $product_cat[] = $product->category->jumia_cat_id;
                
            }
            if (!empty($product->sub_category)) {
                $product_cat[] = $product->sub_category->jumia_cat_id;
            }
         $images = [];     
         
         
         
  
   
          
            //set attributes for variable products
            if ($product->type == 'variable') {
                $variation_attr_data = [];


  
                foreach ($product->variations as $variation) {
                    
                    
                       if ($sync_type == 'new') {
          
               //If media id is set use media id else use image src
                if (!empty($product->image) && in_array('image', $jumia_api_settings->product_fields_for_create)) {
                    if ($this->isValidImage($product->image_path)) {  }
                          $images[] = $product->image_url;
                        
                  
                  
                }
             if ( in_array('image', $jumia_api_settings->product_fields_for_create)) {
                    
               if(count($variation->media) > 0){
               foreach($variation->media as $media){
                   
                 $images[] = asset('/uploads/media/' . rawurlencode($media->file_name));  
               }
                
           }     
              
                }
                      
          
         
      }elseif ($sync_type == 'update') {
          
          
                 //If media id is set use media id else use image src
                if (!empty($product->image) && in_array('image', $jumia_api_settings->product_fields_for_update)) {
                    if ($this->isValidImage($product->image_path)) {  }
                          $images[] = $product->image_url;
                        
                  
                  
                }
             if ( in_array('image', $jumia_api_settings->product_fields_for_update)) {
                    
               if(count($variation->media) > 0){
               foreach($variation->media as $media){
                   
                 $images[] = asset('/uploads/media/' . rawurlencode($media->file_name));  
               }
                
           }     
              
                }
                     
          
          
      } 
                    
                    
                    
                    
                      $price = $jumia_api_settings->product_tax_type == 'exc' ? $variation->default_sell_price : $variation->sell_price_inc_tax;

                    if (!empty($jumia_api_settings->default_selling_price_group)) {
                        $group_prices = $this->productUtil->getVariationGroupPrice($variation->id, $jumia_api_settings->default_selling_price_group, $product->tax_id);
        
                        $price = $jumia_api_settings->product_tax_type == 'exc' ? $group_prices['price_exc_tax'] : $group_prices['price_inc_tax'];
                    }
 
 
          if ($manage_stock) {
                $variation_location_details = $variation->variation_location_details;
                foreach ($variation_location_details as $vld) {
                    if ($vld->location_id == $business_location_id) {
                        $qty_available = $vld->qty_available;
                    }
                }
            }

                    
                   $products[] = [
                        'type' => $product->type == 'single' ? 'single' : 'variable',
                        'sku' => $product->sku,
                        'SellerSku' => $variation->sub_sku,
                        'ProductId' => $variation->sub_sku,
                        'ParentSku' => $first_variation->sub_sku,
                        'name' => $product->name,
                        'NameArEG' => $product->name_ar,
                        'weight' =>  $this->formatDecimalPoint($product->weight),
                        'variation' => $variation->name,
                        'MainImage' => $images,
                        'PrimaryCategory' => $product->category->jumia_cat_id,
                        'Description' => $product->product_description,
                        'DescriptionArEG' => $product->product_description_ar,
                        'Brand' => $product->brand->jumia_brand,
                        'price' => $this->productUtil->num_uf($price),
                        'images' => $images,
                        'Quantity' => $this->productUtil->num_uf($qty_available),
                        'status' => 'inactive',
                    ];
            
                    
                 
                    
                   $variation->jumia_variation_id = 1; 
                   $variation->save(); 
                    
                }

              
            }elseif($product->type == 'single'){
                
                
                               if ($sync_type == 'new') {
          
               //If media id is set use media id else use image src
                if (!empty($product->image) && in_array('image', $jumia_api_settings->product_fields_for_create)) {
                    if ($this->isValidImage($product->image_path)) {    }
                          $images[] = $product->image_url;
                        
                
                  
                }
             if ( in_array('image', $jumia_api_settings->product_fields_for_create)) {
                    
               if(count($first_variation->media) > 0){
               foreach($first_variation->media as $media){
                   
                 $images[] = asset('/uploads/media/' . rawurlencode($media->file_name));  
               }
                
           }     
              
                }
                      
          
         
      }elseif ($sync_type == 'update') {
          
          
                 //If media id is set use media id else use image src
                if (!empty($product->image) && in_array('image', $jumia_api_settings->product_fields_for_update)) {
                    if ($this->isValidImage($product->image_path)) {   }
                          $images[] = $product->image_url;
                        
                 
                  
                }
             if ( in_array('image', $jumia_api_settings->product_fields_for_update)) {
                    
               if(count($first_variation->media) > 0){
               foreach($first_variation->media as $media){
                   
                 $images[] = asset('/uploads/media/' . rawurlencode($media->file_name));  
               }
                
           }     
              
                }
                     
          
          
      }    
                
                
                
                $price = $jumia_api_settings->product_tax_type == 'exc' ? $first_variation->default_sell_price : $first_variation->sell_price_inc_tax;

            if (!empty($jumia_api_settings->default_selling_price_group)) {
                $group_prices = $this->productUtil->getVariationGroupPrice($first_variation->id, $jumia_api_settings->default_selling_price_group, $product->tax_id);

                $price = $jumia_api_settings->product_tax_type == 'exc' ? $group_prices['price_exc_tax'] : $group_prices['price_inc_tax'];
            }

            //Set product stock
         
            if ($manage_stock) {
                $variation_location_details = $first_variation->variation_location_details;
                foreach ($variation_location_details as $vld) {
                    if ($vld->location_id == $business_location_id) {
                        $qty_available = $vld->qty_available;
                    }
                }
            }


                  $products[] = [
                        'type' => $product->type == 'single' ? 'single' : 'variable',
                        'sku' => $product->sku,
                        'SellerSku' => $first_variation->sub_sku,
                        'ProductId' => $first_variation->sub_sku,
                        'ParentSku' => $first_variation->sub_sku,
                        'name' => $product->name,
                        'NameArEG' => $product->name_ar,
                        'weight' =>  $this->formatDecimalPoint($product->weight),
                        'variation' => '',
                        'MainImage' => $images,
                        'PrimaryCategory' => $product->category->jumia_cat_id,
                        'Description' => $product->product_description,
                        'DescriptionArEG' => $product->product_description_ar,
                        'Brand' => $product->brand->jumia_brand,
                        'price' => $this->productUtil->num_uf($price),
                        'images' => $images,
                        'Quantity' => $this->productUtil->num_uf($qty_available),
                        'status' => 'inactive',
                    ];
            
                    
                 
                
                  $first_variation->jumia_variation_id = 1; 
                   $first_variation->save(); 
                
                
                
                
            }

       
           
    
            if (empty($product->jumia_product_id)) {
                
                
             

            
              
            $create_response = $this->syncProd($business_id, $products, 'create', $new_products);
                

                 $product->jumia_product_id = $first_variation->sub_sku;
                   $product->save();   
              

                $product_data['create'][] = $products;
                $new_products[] = $product;

                $created_data[] = $product->sku;
            } else {
              
              

               $update_response = $this->syncProd($business_id, $products, 'update', $updated_products);

        
             
                $product_data['update'][] = $products;
                $updated_data[] = $product->sku;
                $updated_products[] = $product;
            }
        }

      
      
        $new_jumia_product_ids = array_merge($create_response, $update_response);

        //Create log
        if (!empty($created_data)) {
            if ($sync_type == 'new') {
                $this->createSyncLog($business_id, $user_id, 'new_products', 'created', $created_data);
            } elseif ($sync_type == 'update') {
                $this->createSyncLog($business_id, $user_id, 'all_products', 'updated', $updated_data);
            } else {
                $this->createSyncLog($business_id, $user_id, 'all_products', 'created', $created_data);
            }
        }
        if (!empty($updated_data)) {
            $this->createSyncLog($business_id, $user_id, 'all_products', 'updated', $updated_data);
        }

      

        if (empty($created_data) && empty($updated_data)) {
            if ($sync_type == 'new') {
                $this->createSyncLog($business_id, $user_id, 'new_products');
            } else {
                $this->createSyncLog($business_id, $user_id, 'all_products');
            }
        }

        return $all_products;
    }
    
    
    
    
      public function syncStocks($business_id, $user_id, $sync_type, $limit = 1000000, $page = 0)
    {
        //$limit is zero for console command
       if ($page == 0 || $limit == 0) {
          
            
            if ($limit > 0) {
                request()->session()->forget('last_product_synced');
            }
        } /**/

        $last_synced = !empty(session('last_product_synced')) ? session('last_product_synced') : $this->getLastSync($business_id, 'all_products', false);
        //store last_synced if page is 0
        if ($page == 0) {
            session(['last_product_synced' => $last_synced]);
        }/**/
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        $created_data = [];
        $updated_data = [];

        $business_location_id = $jumia_api_settings->location_id;
        $offset = $page * $limit;
        $query = Product::where('business_id', $business_id)
                        ->whereIn('type', ['single', 'variable'])
                        ->where('jumia_disable_sync', 0)
                        ->with(['variations', 'category', 'brand','variations.media', 'sub_category',
                            'variations.variation_location_details',
                            'variations.product_variation',
                            'variations.product_variation.variation_template']);

        if ($limit > 0) {
            $query->limit($limit)
                ->offset($offset);
        }/**/
                        
      
        $query->whereNotNull('jumia_product_id');
        

        //Select products only from selected location
        if (!empty($business_location_id)) {
            $query->ForLocation($business_location_id);
        }

        $all_products = $query->get();
        $product_data = [];
        $new_products = [];
        $updated_products = [];

        if (count($all_products) == 0) {
            request()->session()->forget('last_product_synced');
        }
            $create_response = [];
        $update_response = [];
        
        foreach ($all_products as $product) {
             $create_response = [];
        $update_response = [];
            
            //Get details from first variation for single product only
            $first_variation = $product->variations->first();
            if (empty($first_variation)) {
                continue;
            }

       
          $products = [];

            //Set common data
            $array = [
                'type' => $product->type == 'single' ? 'single' : 'variable',
                'sku' => $product->sku,
                'ParentSku' => $first_variation->sub_sku,
                'name' => $product->name,
            ];
            
            
            

            $manage_stock = false;
            if ($product->enable_stock == 1) {
                $manage_stock = true;
            }

            $qty_available = 0;   
         
      
   
          
            //set attributes for variable products
            if ($product->type == 'variable') {
               


  
                foreach ($product->variations as $variation) {
                    
              
 
          if ($manage_stock) {
                $variation_location_details = $variation->variation_location_details;
                foreach ($variation_location_details as $vld) {
                    if ($vld->location_id == $business_location_id) {
                        $qty_available = $vld->qty_available;
                    }
                }
            }

                    
                   $products[] = [
                        'type' => $product->type == 'single' ? 'single' : 'variable',
                        'sku' => $product->sku,
                        'SellerSku' => $variation->sub_sku,
                        'Quantity' => $this->productUtil->num_uf($qty_available)
                    ];
            
                  
                }

              
            }elseif($product->type == 'single'){
                
                
           
            //Set product stock
         
            if ($manage_stock) {
                $variation_location_details = $first_variation->variation_location_details;
                foreach ($variation_location_details as $vld) {
                    if ($vld->location_id == $business_location_id) {
                        $qty_available = $vld->qty_available;
                    }
                }
            }


                  $products[] = [
                        'type' => $product->type == 'single' ? 'single' : 'variable',
                        'sku' => $product->sku,
                        'SellerSku' => $first_variation->sub_sku,
                 
                        'Quantity' => $this->productUtil->num_uf($qty_available)
                    ];
            
                    
                 
                
                
                
                
                
                
            }

       
           $jumia =  $this->product_stock($business_id,['Action' => 'ProductUpdate'],$products);
             
           
          
        }

     
        return $all_products;
    }
    
    
    

    public function syncProd($business_id, $data, $type, $new_products)
    {
      

        $new_jumia_product_ids = [];
        $count = 0;  
        
        
      
        if ($type == 'create') {
            
            
               $jumia = $this->product_create($business_id,['Action' => 'ProductCreate'],$data);
// dd($jumia->SuccessResponse);   
            
          
            if(!empty($jumia->ErrorResponse)){
                
                
                
            }
            
            
        }
        
        
          if ($type == 'update') {
            
            
               $jumia = $this->product_update($business_id,['Action' => 'ProductUpdate'],$data);
// dd($jumia->SuccessResponse);   
            
             
            if(!empty($jumia->ErrorResponse)){
                
                
                
                
            }
            
            
        }
        
        
        
        
         
      
            
      
     
       
       
       
   

        return $new_jumia_product_ids;
    }




    /**
     * Synchronizes Woocommers Orders with POS sales
     * @param int $business_id
     * @param int $user_id
     * @return void
     */
    public function syncOrders($business_id, $user_id)
    {
        $last_synced = $this->getLastSync($business_id, 'orders', false);
        
        $orders = [];
        
       if(!empty($last_synced)) {
           
              $jumia = $this->woo_client($business_id,['Action' => 'GetOrders','CreatedAfter' => strtotime($last_synced)]);  
       }else{
           
             $jumia = $this->woo_client($business_id,['Action' => 'GetOrders']);  
       }


  
      if(!empty($jumia->SuccessResponse)){
          
          
          
          
      if(!empty($jumia->SuccessResponse->Body->Orders->Order)){
              
              
            $orders = $jumia->SuccessResponse->Body->Orders->Order ;
          
          
        $jumia_sells = Transaction::where('business_id', $business_id)
                                ->whereNotNull('jumia_order_id')
                                ->with('sell_lines', 'sell_lines.product', 'payment_lines')
                                ->get();

        $new_orders = [];
        $updated_orders = [];

        $jumia_api_settings = $this->get_api_settings($business_id);
        $business = Business::find($business_id);

        $skipped_orders = !empty($business->jumia_skipped_orders) ? json_decode($business->jumia_skipped_orders, true) : [];
        
        $business_data = [
            'id' => $business_id,
            'accounting_method' => $business->accounting_method,
            'location_id' => $jumia_api_settings->location_id,
            'pos_settings' => json_decode($business->pos_settings, true),
            'business' => $business
        ];

        $created_data = [];
        $updated_data = [];
        $create_error_data = [];
        $update_error_data = [];

        foreach ($orders as $order) { 
            
            $status = is_array($order->Statuses->Status) ? $order->Statuses->Status[0] : $order->Statuses->Status;
            //Only consider orders modified after last sync  || in_array($status, ['failed','returned'])
            if ((!empty($last_synced) && date('Y-m-d H:i:s', strtotime($order->CreatedAt)) <= strtotime($last_synced) && !in_array($order->OrderId, $skipped_orders) )) {
                continue;
            }
             
         
            //Search if order already exists
            $sell = $jumia_sells->filter(function ($item) use ($order) {
                return $item->jumia_order_id == $order->OrderId;
            })->first();


          
            $order_number = $order->OrderNumber;
            $sell_status = $this->jumiaOrderStatusToPosSellStatus($status, $business_id);

            if ($sell_status == 'draft') {
                $order_number .= " (" . __('sale.draft') . ")";
            }
            if (empty($sell)) {
                $created = $this->createNewSaleFromOrder($business_id, $user_id, $order, $business_data);
                $created_data[] = $order_number;

                if ($created !== true) {
                    $create_error_data[] = $created;
                }
            } else {
                $updated = $this->updateSaleFromOrder($business_id, $user_id, $order, $sell, $business_data);
                $updated_data[] = $order_number;

                if ($updated !== true) {
                    $update_error_data[] = $updated;
                }
            }
        }

        //Create log
        if (!empty($created_data)) {
            $this->createSyncLog($business_id, $user_id, 'orders', 'created', $created_data, $create_error_data);
        }
        if (!empty($updated_data)) {
            $this->createSyncLog($business_id, $user_id, 'orders', 'updated', $updated_data, $update_error_data);
        }

        if (empty($created_data) && empty($updated_data)) {
            $error_data = $create_error_data + $update_error_data;
            $this->createSyncLog($business_id, $user_id, 'orders', null, [], $error_data);
        } 
         
              
            }
      }
       
    }

    /**
     * Creates new sales in POSfrom jumia order list
     * @param id $business_id
     * @param id $user_id
     * @param obj $order
     * @param array $business_data
     */
    public function createNewSaleFromOrder($business_id, $user_id, $order, $business_data)
    {
        $input = $this->formatOrderToSale($business_id, $user_id, $order);

        if (!empty($input['has_error'])) {
            return $input['has_error'];
        }

        $invoice_total = [
            'total_before_tax' => $order->Price,
            'tax' => 0,
        ];

        DB::beginTransaction();

        $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id, false);
        $transaction->jumia_order_id = $order->OrderId;
        $transaction->save();

        //Create sell lines
        $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], false, null, ['jumia_line_items_id' => 'line_item_id'], false);

      //  $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment'], $business_id, $user_id, false);

        if ($input['status'] == 'final') {
            //update product stock
            foreach ($input['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $input['location_id'],
                        $product['quantity']
                    );
                }
            }

            //Update payment status
            $transaction->payment_status = 'due';
            $transaction->save();

            try {
                $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            } catch (PurchaseSellMismatch $e) {
                DB::rollBack();

                $this->add_to_skipped_orders($business_data['business'], $order->OrderId);
                return [
                    'error_type' => 'order_insuficient_product_qty',
                    'order_number' => $order->OrderId,
                    'msg' => $e->getMessage()
                ];
            }
        }

        $this->remove_from_skipped_orders($business_data['business'], $order->OrderId);

        DB::commit();

        return true;
    }

    /**
     * Formats jumia order response to pos sale request
     * @param id $business_id
     * @param id $user_id
     * @param obj $order
     * @param obj $sell = null
     */
    public function formatOrderToSale($business_id, $user_id, $order, $sell = null)
    {
        $jumia_api_settings = $this->get_api_settings($business_id);

        //Create sell line data
        $product_lines = [];
        $ShippingAmount = 0;
        //For updating sell lines
        $sell_lines = [];
        $new_sell_data = [];
        
        if (!empty($sell)) {
            $sell_lines = $sell->sell_lines;
        }

        $items = $this->woo_client($business_id,['Action' => 'GetOrderItems','OrderId' => $order->OrderId]);   
        
          if(!empty($items->SuccessResponse->Body->OrderItems->OrderItem)){
              
           $orderitems =  $items->SuccessResponse->Body->OrderItems->OrderItem ;

if(is_array($orderitems)){
    
    
      foreach ($orderitems as $product_line) {
          /*  $product = Product::where('business_id', $business_id)
                            ->where('jumia_product_id', $product_line->product_id)
                            ->with(['variations'])
                            ->first();*/
                            
   $product = Variation::join('products','variations.product_id','products.id')->where('products.business_id', $business_id)
                            ->where('variations.sub_sku', $product_line->Sku)
                            ->whereNotNull('variations.jumia_variation_id')
                            ->first();
                            
                            
            $line_tax = !empty($product_line->TaxAmount) ? $product_line->TaxAmount : 0;
            $unit_price =  $product_line->ItemPrice - $line_tax;
            
           
            $ShippingAmount =  $product_line->ShippingAmount;
            
            $unit_price_inc_tax = $product_line->ItemPrice;
            
            if (!empty($product)) {

                //Set sale line variation;If single product then first variation
                //else search for jumia_variation_id in all the variations
              /*  if ($product->type == 'single') {
                    $variation = $product->variations->first();
                } else {
                    foreach ($product->variations as $v) {
                        if ($v->jumia_variation_id == $product_line->variation_id) {
                            $variation = $v;
                        }
                    }
                }
*/
             $variation = $product;


                if (empty($variation)) {
                    return ['has_error' =>
                            [
                                'error_type' => 'order_product_not_found',
                                'order_number' => $order->OrderId,
                                'product' => $product_line->Name . ' SKU:' . $product_line->Sku
                            ]
                        ];
                    exit;
                }

                //Check if line tax exists append to sale line data
                $tax_id = null;
             /*   if (!empty($product_line->taxes)) {
                    foreach ($product_line->taxes as $tax) {
                        $pos_tax = TaxRate::where('business_id', $business_id)
                        ->where('jumia_tax_rate_id', $tax->id)
                        ->first();

                        if (!empty($pos_tax)) {
                            $tax_id = $pos_tax->id;
                            break;
                        }
                    }
                }
*/
                $product_data = [
                    'product_id' => $product->id,
                    'unit_price' => $unit_price,
                    'unit_price_inc_tax' => $unit_price_inc_tax,
                    'variation_id' => $variation->id,
                    'quantity' => $product_line->IsProcessable,
                    'enable_stock' => $product->enable_stock,
                    'item_tax' => $line_tax,
                    'tax_id' => $tax_id,
                    'line_item_id' => $product_line->OrderItemId
                ];
                
                //append transaction_sell_lines_id if update
                if (!empty($sell_lines)) {
                    foreach ($sell_lines as $sell_line) {
                        if ($sell_line->jumia_line_items_id ==
                            $product_line->OrderItemId) {
                            $product_data['transaction_sell_lines_id'] = $sell_line->id;
                        }
                    }
                }

                $product_lines[] = $product_data;
            } else {
                return ['has_error' =>
                        [
                            'error_type' => 'order_product_not_found',
                            'order_number' => $order->OrderId,
                            'product' => $product_line->Name . ' SKU:' . $product_line->Sku
                        ]
                    ];
                exit;
            }
        }

    
    
}else{
    
    $product_line = $orderitems;
          /*  $product = Product::where('business_id', $business_id)
                            ->where('jumia_product_id', $product_line->product_id)
                            ->with(['variations'])
                            ->first();*/
                            
   $product = Variation::join('products','variations.product_id','products.id')->where('products.business_id', $business_id)
                            ->where('variations.sub_sku', $product_line->Sku)
                            ->whereNotNull('variations.jumia_variation_id')
                            ->first();
                            
                            
            $line_tax = !empty($product_line->TaxAmount) ? $product_line->TaxAmount : 0;
            $unit_price =  $product_line->ItemPrice - $line_tax;
            
           
            $ShippingAmount =  $product_line->ShippingAmount;
            
            $unit_price_inc_tax = $product_line->ItemPrice;
            
            if (!empty($product)) {

                //Set sale line variation;If single product then first variation
                //else search for jumia_variation_id in all the variations
              /*  if ($product->type == 'single') {
                    $variation = $product->variations->first();
                } else {
                    foreach ($product->variations as $v) {
                        if ($v->jumia_variation_id == $product_line->variation_id) {
                            $variation = $v;
                        }
                    }
                }
*/
             $variation = $product;


                if (empty($variation)) {
                    return ['has_error' =>
                            [
                                'error_type' => 'order_product_not_found',
                                'order_number' => $order->OrderId,
                                'product' => $product_line->Name . ' SKU:' . $product_line->Sku
                            ]
                        ];
                    exit;
                }

                //Check if line tax exists append to sale line data
                $tax_id = null;
             /*   if (!empty($product_line->taxes)) {
                    foreach ($product_line->taxes as $tax) {
                        $pos_tax = TaxRate::where('business_id', $business_id)
                        ->where('jumia_tax_rate_id', $tax->id)
                        ->first();

                        if (!empty($pos_tax)) {
                            $tax_id = $pos_tax->id;
                            break;
                        }
                    }
                }
*/
                $product_data = [
                    'product_id' => $product->id,
                    'unit_price' => $unit_price,
                    'unit_price_inc_tax' => $unit_price_inc_tax,
                    'variation_id' => $variation->id,
                    'quantity' => $product_line->IsProcessable,
                    'enable_stock' => $product->enable_stock,
                    'item_tax' => $line_tax,
                    'tax_id' => $tax_id,
                    'line_item_id' => $product_line->OrderItemId
                ];
                
                //append transaction_sell_lines_id if update
                if (!empty($sell_lines)) {
                    foreach ($sell_lines as $sell_line) {
                        if ($sell_line->jumia_line_items_id ==
                            $product_line->OrderItemId) {
                            $product_data['transaction_sell_lines_id'] = $sell_line->id;
                        }
                    }
                }

                $product_lines[] = $product_data;
            } else {
                return ['has_error' =>
                        [
                            'error_type' => 'order_product_not_found',
                            'order_number' => $order->OrderId,
                            'product' => $product_line->Name . ' SKU:' . $product_line->Sku
                        ]
                    ];
                exit;
            }
        
    
}

      
        //Get customer details
        $order_customer_id = '';

        $customer_details = [];

        //If Customer empty skip get guest customer details from billing address
        if (empty($order_customer_id)) {
            $f_name = !empty($order->AddressBilling->FirstName) ? $order->AddressBilling->FirstName : '';
            $l_name = !empty($order->AddressBilling->LastName) ? $order->AddressBilling->LastName : '';
            $customer_details = [
                    'first_name' => $f_name,
                    'last_name' => $l_name,
                    'email' => !empty($order->AddressBilling->CustomerEmail) ? $order->AddressBilling->CustomerEmail : null,
                    'name' => $f_name . ' ' . $l_name,
                    'mobile' => $order->AddressBilling->Phone,
                    'landline' => $order->AddressBilling->Phone2,
                    'address_line_1' => !empty($order->AddressBilling->Address1) ? $order->AddressBilling->Address1 : null,
                    'address_line_2' => !empty($order->AddressBilling->Address2) ? $order->AddressBilling->Address2 : null,
                    'city' => !empty($order->AddressBilling->Region) ? $order->AddressBilling->Region : null,
                    'state' => !empty($order->AddressBilling->city) ? $order->AddressBilling->city : null,
                    'country' => !empty($order->AddressBilling->country) ? $order->AddressBilling->country : null,
                    'zip_code' => null
                ];
        } 
        
        
        
        
        if (!empty($customer_details['mobile'])) {
            $customer = Contact::where('business_id', $business_id)
                            ->where('mobile', $customer_details['mobile'])
                            ->OnlyCustomers()
                            ->first();
        }
        
     
         
                
                
                
              
              
        //If customer not found create new
        if (empty($customer)) {
            $ref_count = $this->transactionUtil->setAndGetReferenceCount('contacts', $business_id);
            $contact_id = $this->transactionUtil->generateReferenceNumber('contacts', $ref_count, $business_id);
               
               
          
                
                
                
            $customer_data = [
                'business_id' => $business_id,
                'type' => 'customer',
                'first_name' => $customer_details['first_name'],
                'last_name' => $customer_details['last_name'],
                'name' => $customer_details['name'],
                'email' => $customer_details['email'],
                'contact_id' => $contact_id,
                'mobile' => $customer_details['mobile'],
                'city' => $customer_details['state'],
                'state' => $customer_details['state'],
                'country' => $customer_details['country'],
                'created_by' => $user_id,
                'address_line_1' => !empty($customer_details['address_line_1']) ?  $customer_details['address_line_1']  : $order->shipping->address_1  ,
                'address_line_2' => $customer_details['address_line_2'],
                'zip_code' => $customer_details['zip_code']
            ];

            //if name is blank make email address as name
            if (empty(trim($customer_data['name']))) {
                $customer_data['first_name'] = $customer_details['email'];
                $customer_data['name'] = $customer_details['email'];
            }
            
           
            
            
            
            $customer = Contact::create($customer_data);
            
                 $address =  new Address  ; 
                 $address->contact_id = $customer->id ;
                 $address->business_id = $business_id ;
                 $address->country = $customer->country ;
                 $address->city = $customer->city ;
                 $address->state = $customer->state ;
                 $address->address = $customer_details['address_line_1'] ;
                 $address->name = 'عنوان1 ' ;
                 $address->phone = $customer->landline ;
                 $address->mobile = $customer->mobile ;
                 $address->save() ;  
        }
      
            
            
          $address =   Address::where('contact_id',$customer->id)->where('business_id',$business_id)->first();
          if(empty($address)){
              
              
                 $address =  new Address  ; 
                 $address->contact_id = $customer->id ;
                 $address->business_id = $business_id ;
                 $address->country = $customer->country ;
                 $address->city = $customer->city ;
                 $address->state = $customer->state ;
                 $address->address = $customer_details['address_line_1'] ;
                 $address->name = 'عنوان1 ' ;
                 $address->phone = $customer->landline ;
                 $address->mobile = $customer->mobile ;
                 $address->save() ;    
              
              
          }else{
              
             
                
                 $address->country = $customer_details['country'] ;
                 $address->city = $customer_details['state'];
                 $address->state = $customer_details['state'] ;
                 $address->address = $customer_details['address_line_1'] ;
              
                 $address->mobile = $customer_details['mobile'] ;
                 $address->save() ;    
              
              
          }
            
            
   
     
         $status = is_array($order->Statuses->Status) ? $order->Statuses->Status[0] : $order->Statuses->Status;
        $sell_status = $this->jumiaOrderStatusToPosSellStatus($status, $business_id);
        $shipping_status = $this->jumiaOrderStatusToPosShippingStatus($status, $business_id);
        $shipping_address = [];
        if (!empty($order->AddressShipping->FirstName)) {
            $shipping_address[] = $order->AddressShipping->FirstName . ' ' . $order->AddressShipping->LastName;
        }
       
        if (!empty($order->AddressShipping->Address1)) {
            $shipping_address[] = $order->AddressShipping->Address1;
        }
        if (!empty($order->AddressShipping->Address2)) {
            $shipping_address[] = $order->AddressShipping->Address2;
        }
        if (!empty($order->AddressShipping->Region)) {
            $shipping_address[] = $order->AddressShipping->Region;
        }
        if (!empty($order->AddressShipping->City)) {
            $shipping_address[] = $order->AddressShipping->City;
        }
        if (!empty($order->AddressShipping->Country)) {
            $shipping_address[] = $order->AddressShipping->Country;
        }
       
        $addresses['shipping_address'] = [
            'shipping_name' =>  $order->AddressShipping->FirstName . ' ' . $order->AddressShipping->LastName,
            
            'shipping_address_line_1' => $order->AddressShipping->Address1,
            'shipping_address_line_2' => $order->AddressShipping->Address2,
            'shipping_city' => $order->AddressShipping->Region,
            'shipping_state' => $order->AddressShipping->City,
            'shipping_country' => $order->AddressShipping->Country 
        ];
        $addresses['billing_address'] = [
            'billing_name' => $order->AddressBilling->FirstName . ' ' . $order->AddressBilling->LastName,
          
            'billing_address_line_1' => $order->AddressBilling->Address1,
            'billing_address_line_2' => $order->AddressBilling->Address2,
            'billing_city' => $order->AddressBilling->Region,
            'billing_state' => $order->AddressBilling->City,
            'billing_country' => $order->AddressBilling->Country 
        ];

        $shipping_lines_array = [
             
          $order->AddressShipping->Address1,
          
            $order->AddressShipping->Region,
              $order->AddressShipping->City,
           $order->AddressShipping->Country 
        ];
      
  // \Log::info(json_encode($customer));
        $new_sell_data = [
            'business_id' => $business_id,
            'location_id' => $jumia_api_settings->location_id,
            'contact_id' => $customer->id,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
          
            'final_total' => $order->Price,
            'created_by' => $user_id,
            'address_id' => !empty($address) ? $address->id : null,
            'status' => $sell_status == 'quotation' ? 'draft' : $sell_status,
            'is_quotation' => $sell_status == 'quotation' ? 1 : 0,
            'sub_status' => $sell_status == 'quotation' ? 'quotation' : null,
            'payment_status' => 'paid',
            'additional_notes' => '',
            'transaction_date' => date('Y-m-d H:i:s', strtotime($order->CreatedAt)),
            'customer_group_id' => $customer->customer_group_id,
            'tax_rate_id' => null,
            'sale_note' => null,
            'commission_agent' => null,
            'invoice_no' => $order->OrderNumber,
            'order_addresses' => json_encode($addresses),
            'shipping_charges' => !empty($ShippingAmount) ? $ShippingAmount : 0,
            'shipping_details' => !empty($shipping_lines_array) ? implode(', ', $shipping_lines_array) : '',
            'shipping_status' => $shipping_status,
            'shipping_address' => implode(', ', $shipping_address)
        ];

        $payment = [
            'amount' => $order->Price,
            'method' => 'cash',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_security' => '',
            'cheque_number' =>'',
            'bank_account_number' => '',
            'note' => '',
            'paid_on' => date('Y-m-d H:i:s', strtotime($order->CreatedAt))
        ];

        if (!empty($sell) && count($sell->payment_lines) > 0) {
            $payment['payment_id'] = $sell->payment_lines->first()->id;
        }

        $new_sell_data['products'] = $product_lines;
        $new_sell_data['payment'] = [$payment];

}


        return $new_sell_data;
    }

    /**
     * Updates existing sale
     * @param id $business_id
     * @param id $user_id
     * @param obj $order
     * @param obj $sell
     * @param array $business_data
     */
    public function updateSaleFromOrder($business_id, $user_id, $order, $sell, $business_data)
    {
        $input = $this->formatOrderToSale($business_id, $user_id, $order, $sell);

        if (!empty($input['has_error'])) {
            return $input['has_error'];
        }

        $invoice_total = [
            'total_before_tax' => $order->Price,
            'tax' => 0,
        ];

        $status_before = $sell->status;

        DB::beginTransaction();
        $transaction = $this->transactionUtil->updateSellTransaction($sell, $business_id, $input, $invoice_total, $user_id, false, false);

        //Update Sell lines
        $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before, [], false);

        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment'], null, null, false);

        //Update payment status
        $transaction->payment_status = 'due';
        $transaction->save();

        //Update product stock
        $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input, false);

        try {
            $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business_data, $deleted_lines);
        } catch (PurchaseSellMismatch $e) {
            DB::rollBack();
            return [
                'error_type' => 'order_insuficient_product_qty',
                'order_number' => $order->OrderId,
                'msg' => $e->getMessage()
            ];
        }

        DB::commit();

        return true;
    }

    /**
     * Creates sync log in the database
     * @param id $business_id
     * @param id $user_id
     * @param string $type
     * @param array $errors = null
     */
    public function createSyncLog($business_id, $user_id, $type, $operation = null, $data = [], $errors = null)
    {
        JumiaSyncLog::create([
            'business_id' => $business_id,
            'sync_type' => $type,
            'created_by' => $user_id,
            'operation_type' => $operation,
            'data' => !empty($data) ? json_encode($data) : null,
            'details' => !empty($errors) ? json_encode($errors) : null
        ]);
    }

    /**
     * Retrives last synced date from the database
     * @param id $business_id
     * @param string $type
     * @param bool $for_humans = true
     */
    public function getLastSync($business_id, $type, $for_humans = true)
    {
        $last_sync = JumiaSyncLog::where('business_id', $business_id)
                            ->where('sync_type', $type)
                            ->max('created_at');

        //If last reset present make last sync to null
        $last_reset = JumiaSyncLog::where('business_id', $business_id)
                            ->where('sync_type', $type)
                            ->where('operation_type', 'reset')
                            ->max('created_at');
        if (!empty($last_reset) && !empty($last_sync) && $last_reset >= $last_sync) {
            $last_sync = null;
        }

        if (!empty($last_sync) && $for_humans) {
            $last_sync = \Carbon::createFromFormat('Y-m-d H:i:s', $last_sync)->diffForHumans();
        }
        return $last_sync;
    }

    public function jumiaOrderStatusToPosSellStatus($status, $business_id)
    {
        $default_status_array = [
            	'pending' => 'draft',
            	'shipped' => 'draft',
            	'processing' => 'draft',
            	'ready_to_ship' => 'draft',
            	'return_rejected' => 'draft',
            	'return_shipped_by_customer' => 'draft',
            	'return_waiting_for_approval' => 'draft',
            	'failed' => 'draft',
        			'returned' => 'draft',
        			'canceled' => 'draft',
        			'delivered' => 'draft'
        ];

        $api_settings = $this->get_api_settings($business_id);

        $status_settings = $api_settings->order_statuses ?? null;
        $sale_status = !empty($status_settings) ? $status_settings->$status : null;
        $sale_status = empty($sale_status) && array_key_exists($status, $default_status_array) ? $default_status_array[$status] : $sale_status;
        $sale_status = empty($sale_status) ? 'final' : $sale_status;


        return $sale_status;
    }

    public function jumiaOrderStatusToPosShippingStatus($status, $business_id)
    {
        $api_settings = $this->get_api_settings($business_id);

        $status_settings = $api_settings->shipping_statuses ?? null;

        $shipping_status = !empty($status_settings) ? $status_settings->$status : 'pending';

        return $shipping_status;
    }

    /**
     * Splits response to list of 100 and merges all
     * @param int $business_id
     * @param string $endpoint
     * @param array $params = []
     *
     * @return array
     */

    /**
     * Retrives all tax rates from jumia api
     * @param id $business_id
     *
     * @param obj $tax_rates
     */
    public function getTaxRates($business_id)
    {
        $tax_rates = '';
        return $tax_rates;
    }

    public function getLastStockUpdated($location_id, $product_id)
    {
        $last_updated = VariationLocationDetails::where('location_id', $location_id)
                                    ->where('product_id', $product_id)
                                    ->max('updated_at');

        return $last_updated;
    }

    private function formatDecimalPoint($number, $type = 'currency') {

        $precision = 4;
        $currency_precision = config('constants.currency_precision');
        $quantity_precision = config('constants.quantity_precision');

        if ($type == 'currency' && !empty($currency_precision)) {
            $precision = $currency_precision;
        }
        if ($type == 'quantity' && !empty($quantity_precision)) {
            $precision = $quantity_precision;
        }

        return number_format((float) $number, $precision, ".", "");
    }

    public function isValidImage($path)
    {
        $valid_extenstions = ['jpg', 'jpeg', 'png', 'gif'];

        return !empty($path) && file_exists($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $valid_extenstions);
    }
}
