<?php

namespace Modules\Jumia\Http\traits;

use App\Business;
use App\Category;
use App\Contact;
use App\Address;
use App\Exceptions\PurchaseSellMismatch;
use App\Product;
use App\TaxRate;
use App\Transaction;
use App\Utils\ProductUtil;

use App\Utils\TransactionUtil;

use App\Utils\Util;
use App\Utils\ContactUtil;

use App\VariationLocationDetails;
use App\VariationTemplate;
//use Automattic\cscart\Client;

use DB;
use Modules\Jumia\Entities\JumiaSyncLog;

use Modules\Jumia\Exceptions\jumiaError;

trait  ApiTrait
{
  
   public function get_api_settings($business_id)
    {
        $business = Business::find($business_id);
        $jumia_api_settings = json_decode($business->jumia_api_settings);
        return $jumia_api_settings;
    }
    
      public function woo_client($business_id,$parameter = [])
    {
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        if (empty($jumia_api_settings)) {
            throw new jumiaError(__("jumia::lang.unable_to_connect"));
        }

        $email =  $jumia_api_settings->jumia_consumer_key;
        $api_key =  $jumia_api_settings->jumia_consumer_secret;

         
         
        date_default_timezone_set("UTC");
        
        // The current time. Needed to create the Timestamp parameter below.
        $now = new \DateTime();
        
        // The parameters for our GET request. These will get signed.
        $parameterz = array(
            // The user ID for which we are making the call.
            'UserID' => $email,
        
            // The API version. Currently must be 1.0
            'Version' => '1.0',
        
            // The API method to call.
        //    'Action' => 'GetProducts',
        
            // The format of the result.
            'Format' => 'JSON',
        
            // The current time formatted as ISO8601
            'Timestamp' => $now->format(\DateTime::ISO8601)
        );
        
       $parameters = array_merge($parameter,$parameterz); 
        
        
        // Sort parameters by name.
        ksort($parameters);
        
        // URL encode the parameters.
        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        
        // Concatenate the sorted and URL encoded parameters into a string.
        $concatenated = implode('&', $encoded);
        
        // The API key for the user as generated in the Seller Center GUI.
        // Must be an API key associated with the UserID parameter.
      //  $api_key = '36688894553ead0307d3e4947d75e2c06f5952f4';
        
        // Compute signature and add it to the parameters.
        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));
            
            // Replace with the URL of your API host.
        $url =  $jumia_api_settings->jumia_app_url;
        
        // Build Query String
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        
        // Open cURL connection
        $ch = curl_init();
        
         
        curl_setopt($ch, CURLOPT_URL, $url."?".$queryString);
        
        // Save response to the variable $data
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
        
        $data = curl_exec($ch);
        
        // Close Curl connection
        curl_close($ch);
        
     //   dd(json_decode($data));
            
            
        return json_decode($data);
 
    }

      public function product_update($business_id,$parameter = [],$products = [])
    {
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        if (empty($jumia_api_settings)) {
            throw new jumiaError(__("jumia::lang.unable_to_connect"));
        }

        $email =  $jumia_api_settings->jumia_consumer_key;
        $api_key =  $jumia_api_settings->jumia_consumer_secret;

         
         
        date_default_timezone_set("UTC");
        
        // The current time. Needed to create the Timestamp parameter below.
        $now = new \DateTime();
        
        // The parameters for our GET request. These will get signed.
        $parameterz = array(
            // The user ID for which we are making the call.
            'UserID' => $email,
        
            // The API version. Currently must be 1.0
            'Version' => '1.0',
        
            // The API method to call.
        //    'Action' => 'GetProducts',
        
            // The format of the result.
            'Format' => 'JSON',
        
            // The current time formatted as ISO8601
            'Timestamp' => $now->format(\DateTime::ISO8601)
        );
        
       $parameters = array_merge($parameter,$parameterz); 
        
        
        // Sort parameters by name.
        ksort($parameters);
        
        // URL encode the parameters.
        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        
        // Concatenate the sorted and URL encoded parameters into a string.
        $concatenated = implode('&', $encoded);
        
        // The API key for the user as generated in the Seller Center GUI.
        // Must be an API key associated with the UserID parameter.
      //  $api_key = '36688894553ead0307d3e4947d75e2c06f5952f4';
        
        // Compute signature and add it to the parameters.
        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));
            
            // Replace with the URL of your API host.
        $url =  $jumia_api_settings->jumia_app_url;
        
        // Build Query String
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        
        
            $details = '';
           $is_image = false;
         foreach($products as $product){
              if(!empty($product['images'])){
                 
                  $is_image = true;  
                 
             }
            $details .= '<Product>
                <SellerSku>'.$product['SellerSku'].'</SellerSku>
                <ParentSku>'.$product['ParentSku'].'</ParentSku>
             
                
                <Status>'.$product['status'].'</Status>
                <Name>'.$product['name'].'</Name>
          
                <PrimaryCategory>'.$product['PrimaryCategory'].'</PrimaryCategory>
                <Quantity>'.$product['Quantity'].'</Quantity>
              
                <Description><![CDATA['.$product['Description'].']]></Description>
                <Brand>'.$product['Brand'].'</Brand>
                <Price>'.$product['price'].'</Price>
               
                
                <ProductId>'.$product['SellerSku'].'</ProductId>
                <ProductData>
                  <NameArEG>'.$product['NameArEG'].'</NameArEG>
                  <ProductWeight>'.$product['weight'].'</ProductWeight>
                  <DescriptionArEG><![CDATA['.$product['DescriptionArEG'].']]></DescriptionArEG>
                  <ShortDescription><![CDATA['.$product['Description'].']]></ShortDescription>
                  <ShortDescriptionArEG><![CDATA['.$product['DescriptionArEG'].']]></ShortDescriptionArEG>
                </ProductData>
               
              </Product>';  
             
         }
      
        // Open cURL connection
        $ch = curl_init();
        
         
    //    curl_setopt($ch, CURLOPT_URL, $url."?".$queryString);
        
        // Save response to the variable $data
     //   curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
     curl_setopt_array($ch, array(
  CURLOPT_URL => $url."?".$queryString,
        CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="UTF-8" ?>
            <Request>
            
            
             
            '.$details.'
            
            
            
            </Request>',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/xml',
            'Cookie: ABTests=%5B%7B%22name%22%3A%22CLP%22%2C%22scenario%22%3A%22A%22%2C%22updatedAt%22%3A1626260276%7D%2C%7B%22name%22%3A%22SearchPerso%22%2C%22scenario%22%3A%22Y%22%2C%22updatedAt%22%3A1630072684%7D%5D; __cf_bm=gNH6WvhCDlgWwoQNEdycOzTsX1Ura0mM5R_aDPn8GVQ-1655482686-0-AYCGmPQpFNhSHcI8XmK4BtyUKjDfWDdPUYjPo2PRo5ji7C1dJdfHL7BiYGiijDxP33NR47lmQ9SqYX3X62+Y2Sk=; newsletter=1; sb-closed=true; sponsoredUserId=1691010423398811962ab3c6a2cb17; userLanguage=en_EG'
          ),
        ));


        $data = curl_exec($ch);
        
        // Close Curl connection
        curl_close($ch);
        
     //   dd(json_decode($data));
             if($is_image){
                
                  $this->product_images($business_id,['Action' => 'Image'],$products);   
            }
      
            
        return json_decode($data);
 
    }
    
    
     public function product_create($business_id,$parameter = [],$products = [])
    {
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        if (empty($jumia_api_settings)) {
            throw new jumiaError(__("jumia::lang.unable_to_connect"));
        }

        $email =  $jumia_api_settings->jumia_consumer_key;
        $api_key =  $jumia_api_settings->jumia_consumer_secret;

         
         
        date_default_timezone_set("UTC");
        
        // The current time. Needed to create the Timestamp parameter below.
        $now = new \DateTime();
        
        // The parameters for our GET request. These will get signed.
        $parameterz = array(
            // The user ID for which we are making the call.
            'UserID' => $email,
        
            // The API version. Currently must be 1.0
            'Version' => '1.0',
        
            // The API method to call.
        //    'Action' => 'GetProducts',
        
            // The format of the result.
            'Format' => 'JSON',
        
            // The current time formatted as ISO8601
            'Timestamp' => $now->format(\DateTime::ISO8601)
        );
        
       $parameters = array_merge($parameter,$parameterz); 
        
        
        // Sort parameters by name.
        ksort($parameters);
        
        // URL encode the parameters.
        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        
        // Concatenate the sorted and URL encoded parameters into a string.
        $concatenated = implode('&', $encoded);
        
        // The API key for the user as generated in the Seller Center GUI.
        // Must be an API key associated with the UserID parameter.
      //  $api_key = '36688894553ead0307d3e4947d75e2c06f5952f4';
        
        // Compute signature and add it to the parameters.
        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));
            
            // Replace with the URL of your API host.
        $url =  $jumia_api_settings->jumia_app_url;
        
        // Build Query String
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        
        // Open cURL connection
        $ch = curl_init();
        
         
         $details = '';
         $is_image = false;
         
         foreach($products as $product){
             
             if(!empty($product['images'])){
                 
                  $is_image = true;  
                 
             }
             
            $details .= '<Product>
                <SellerSku>'.$product['SellerSku'].'</SellerSku>
                <ParentSku>'.$product['ParentSku'].'</ParentSku>
                
                <Status>'.$product['status'].'</Status>
                <Name>'.$product['name'].'</Name>
                <Variation>'.$product['variation'].'</Variation>
                <PrimaryCategory>'.$product['PrimaryCategory'].'</PrimaryCategory>
                <Quantity>'.$product['Quantity'].'</Quantity>
             
                <Description><![CDATA['.$product['Description'].']]></Description>
                <Brand>'.$product['Brand'].'</Brand>
                <Price>'.$product['price'].'</Price>
                 
                 
                <ProductId>'.$product['SellerSku'].'</ProductId>
                <ProductData>
                  <NameArEG>'.$product['NameArEG'].'</NameArEG>
                  <ProductWeight>'.$product['weight'].'</ProductWeight>
                  <DescriptionArEG><![CDATA['.$product['DescriptionArEG'].']]></DescriptionArEG>
                  <ShortDescription><![CDATA['.$product['Description'].']]></ShortDescription>
                  <ShortDescriptionArEG><![CDATA['.$product['DescriptionArEG'].']]></ShortDescriptionArEG>
                </ProductData>
               
              </Product>';  
             
         }
         
         //  dd($details);
         
    //    curl_setopt($ch, CURLOPT_URL, $url."?".$queryString);
        
        // Save response to the variable $data
     //   curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
     curl_setopt_array($ch, array(
  CURLOPT_URL => $url."?".$queryString,
        CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="UTF-8" ?>
            <Request>
            
            
            '.$details.'
            
            
            </Request>',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/xml',
            'Cookie: ABTests=%5B%7B%22name%22%3A%22CLP%22%2C%22scenario%22%3A%22A%22%2C%22updatedAt%22%3A1626260276%7D%2C%7B%22name%22%3A%22SearchPerso%22%2C%22scenario%22%3A%22Y%22%2C%22updatedAt%22%3A1630072684%7D%5D; __cf_bm=gNH6WvhCDlgWwoQNEdycOzTsX1Ura0mM5R_aDPn8GVQ-1655482686-0-AYCGmPQpFNhSHcI8XmK4BtyUKjDfWDdPUYjPo2PRo5ji7C1dJdfHL7BiYGiijDxP33NR47lmQ9SqYX3X62+Y2Sk=; newsletter=1; sb-closed=true; sponsoredUserId=1691010423398811962ab3c6a2cb17; userLanguage=en_EG'
          ),
        ));


        $data = curl_exec($ch);
        
        // Close Curl connection
        curl_close($ch);
        
      //   dd(json_decode($data));
            if($is_image){
                
                  $this->product_images($business_id,['Action' => 'Image'],$products);   
            }
      
            
        return json_decode($data);
 
    }





  public function product_stock($business_id,$parameter = [],$products = [])
    {
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        if (empty($jumia_api_settings)) {
            throw new jumiaError(__("jumia::lang.unable_to_connect"));
        }

        $email =  $jumia_api_settings->jumia_consumer_key;
        $api_key =  $jumia_api_settings->jumia_consumer_secret;

         
         
        date_default_timezone_set("UTC");
        
        // The current time. Needed to create the Timestamp parameter below.
        $now = new \DateTime();
        
        // The parameters for our GET request. These will get signed.
        $parameterz = array(
            // The user ID for which we are making the call.
            'UserID' => $email,
        
            // The API version. Currently must be 1.0
            'Version' => '1.0',
        
            // The API method to call.
        //    'Action' => 'GetProducts',
        
            // The format of the result.
            'Format' => 'JSON',
        
            // The current time formatted as ISO8601
            'Timestamp' => $now->format(\DateTime::ISO8601)
        );
        
       $parameters = array_merge($parameter,$parameterz); 
        
        
        // Sort parameters by name.
        ksort($parameters);
        
        // URL encode the parameters.
        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        
        // Concatenate the sorted and URL encoded parameters into a string.
        $concatenated = implode('&', $encoded);
        
        // The API key for the user as generated in the Seller Center GUI.
        // Must be an API key associated with the UserID parameter.
      //  $api_key = '36688894553ead0307d3e4947d75e2c06f5952f4';
        
        // Compute signature and add it to the parameters.
        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));
            
            // Replace with the URL of your API host.
        $url =  $jumia_api_settings->jumia_app_url;
        
        // Build Query String
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        
        // Open cURL connection
        $ch = curl_init();
        
         
         $details = '';
       
         
         foreach($products as $product){
             
         
              
            $details .= '<Product>
                <SellerSku>'.$product['SellerSku'].'</SellerSku>
                <Quantity>'.$product['Quantity'].'</Quantity>
             
             
               
              </Product>';  
             
         }
         
         //  dd($details);
         
    //    curl_setopt($ch, CURLOPT_URL, $url."?".$queryString);
        
        // Save response to the variable $data
     //   curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
     curl_setopt_array($ch, array(
  CURLOPT_URL => $url."?".$queryString,
        CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="UTF-8" ?>
            <Request>
            
            
            '.$details.'
            
            
            </Request>',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/xml',
            'Cookie: ABTests=%5B%7B%22name%22%3A%22CLP%22%2C%22scenario%22%3A%22A%22%2C%22updatedAt%22%3A1626260276%7D%2C%7B%22name%22%3A%22SearchPerso%22%2C%22scenario%22%3A%22Y%22%2C%22updatedAt%22%3A1630072684%7D%5D; __cf_bm=gNH6WvhCDlgWwoQNEdycOzTsX1Ura0mM5R_aDPn8GVQ-1655482686-0-AYCGmPQpFNhSHcI8XmK4BtyUKjDfWDdPUYjPo2PRo5ji7C1dJdfHL7BiYGiijDxP33NR47lmQ9SqYX3X62+Y2Sk=; newsletter=1; sb-closed=true; sponsoredUserId=1691010423398811962ab3c6a2cb17; userLanguage=en_EG'
          ),
        ));


        $data = curl_exec($ch);
        
        // Close Curl connection
        curl_close($ch);
        
      //   dd(json_decode($data));
         
            
        return json_decode($data);
 
    }



    public function product_images($business_id,$parameter = [],$products = [])
    {
        
        $jumia_api_settings = $this->get_api_settings($business_id);
        if (empty($jumia_api_settings)) {
            throw new jumiaError(__("jumia::lang.unable_to_connect"));
        }

        $email =  $jumia_api_settings->jumia_consumer_key;
        $api_key =  $jumia_api_settings->jumia_consumer_secret;

         
         
        date_default_timezone_set("UTC");
        
        // The current time. Needed to create the Timestamp parameter below.
        $now = new \DateTime();
        
        // The parameters for our GET request. These will get signed.
        $parameterz = array(
            // The user ID for which we are making the call.
            'UserID' => $email,
        
            // The API version. Currently must be 1.0
            'Version' => '1.0',
        
            // The API method to call.
        //    'Action' => 'GetProducts',
        
            // The format of the result.
            'Format' => 'JSON',
        
            // The current time formatted as ISO8601
            'Timestamp' => $now->format(\DateTime::ISO8601)
        );
        
       $parameters = array_merge($parameter,$parameterz); 
        
        
        // Sort parameters by name.
        ksort($parameters);
        
        // URL encode the parameters.
        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        
        // Concatenate the sorted and URL encoded parameters into a string.
        $concatenated = implode('&', $encoded);
        
        // The API key for the user as generated in the Seller Center GUI.
        // Must be an API key associated with the UserID parameter.
      //  $api_key = '36688894553ead0307d3e4947d75e2c06f5952f4';
        
        // Compute signature and add it to the parameters.
        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));
            
            // Replace with the URL of your API host.
        $url =  $jumia_api_settings->jumia_app_url;
        
        // Build Query String
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        
        // Open cURL connection
        $ch = curl_init();
        
         
         $details = '';
         
         foreach($products as $product){
             $images = '';
             
             
            if(!empty($product['images'])) {
               foreach($product['images'] as $image){
                   
                 $images .= '<Image>'.$image.'</Image>';   
                   
               } 
                
                
            }
             
             
            $details .= '  <ProductImage>
            <SellerSku>'.$product['SellerSku'].'</SellerSku>
            <Images>
             '.$images.'
            </Images>
          </ProductImage>
         ';  
             
         }
         
         
         
       //   dd($details);
         
    //    curl_setopt($ch, CURLOPT_URL, $url."?".$queryString);
        
        // Save response to the variable $data
     //   curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
     curl_setopt_array($ch, array(
  CURLOPT_URL => $url."?".$queryString,
        CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="UTF-8" ?>
            <Request>
            
            
            '.$details.'
            
            
            </Request>',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/xml',
            'Cookie: ABTests=%5B%7B%22name%22%3A%22CLP%22%2C%22scenario%22%3A%22A%22%2C%22updatedAt%22%3A1626260276%7D%2C%7B%22name%22%3A%22SearchPerso%22%2C%22scenario%22%3A%22Y%22%2C%22updatedAt%22%3A1630072684%7D%5D; __cf_bm=gNH6WvhCDlgWwoQNEdycOzTsX1Ura0mM5R_aDPn8GVQ-1655482686-0-AYCGmPQpFNhSHcI8XmK4BtyUKjDfWDdPUYjPo2PRo5ji7C1dJdfHL7BiYGiijDxP33NR47lmQ9SqYX3X62+Y2Sk=; newsletter=1; sb-closed=true; sponsoredUserId=1691010423398811962ab3c6a2cb17; userLanguage=en_EG'
          ),
        ));


        $data = curl_exec($ch);
        
        // Close Curl connection
        curl_close($ch);
        
      //   dd(json_decode($data));
            
            
        return json_decode($data);
 
    }

  /*----------------------------------------------------------------------------------------------------------------------*/

// Category
    public function curl_category($business_id,$category,$id,$parent_id,$type)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new jumiaError(__("jumia::lang.unable_to_connect"));
        }
        
        if($parent_id !=0 ){
            
          $cscart_cat_id = Category::find($parent_id);
          $parent_id = $cscart_cat_id->jumia_cat_id;
          
        }
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
        
        
             $curl = curl_init();
            
            curl_setopt_array($curl, array(
              CURLOPT_URL => $cscart_api_settings->cscart_app_url.'/api/categories/',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => array('category' => $category ,'parent_id' => $parent_id),
              CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token.'',
               
                'Cookie: sid_admin_8299a=cce688b02679eeebdc0054e6c5856493-0-A'
              ),
            ));
            
            $response = curl_exec($curl);
              
            curl_close($curl);
           \Log::info($response);
            $response =  json_decode($response) ;
     
     
        return $response;
    }
    
  
    public function curl_update_category($business_id,$category,$id,$parent_id,$type)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
        if($parent_id !=0){
            
          $cscart_cat_id = Category::find($parent_id);
          $parent_id = $cscart_cat_id->cscart_cat_id;
        }
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
        
    
     // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/categories/'.$id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"lang_code\":\"en\",\n    \"category\":\"$category\",\n    \"parent_id\":$parent_id\n}");

$headers = array();
$headers[] =  'Authorization: Bearer '.$token.'';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch); 


   
        
 $response =  json_decode($result) ;
/*if (curl_errno($ch)) {
    
    \Log::info($result);
    echo 'Error:' . curl_error($ch);
}

*/
    curl_close($ch);



        return $response;
        
        
        
        
        
    }
  
  /*----------------------------------------------------------------------------------------------------------------------*/
    // Products
  public function curl_product($business_id,$product)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    
        $image = $product['images'];
        $categories = json_encode($product['categories']);
        $category = $product['category'];
        $sku = $product['sku'];
        $description = $product['description'];
        $name = $product['name'];
        $stock_quantity = $product['stock_quantity'];
        $regular_price = $product['regular_price'];
// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/products');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

curl_setopt($ch, CURLOPT_POSTFIELDS, "{
	\"product\": \"$name\",
	\"category_ids\": $categories,
	\"main_category\": $category,
	\"price\": $regular_price,
	\"amount\": $stock_quantity,
	\"full_description\": \"$description\",
	\"short_description\": \"$description\",
	\"product_code\": \"$sku\",
	\"company_id\": 1,
	\"status\": \"A\",
	\"main_pair\": {
		\"detailed\": {
			\"image_path\": \"$image\"
		}
	},
	\"image_pairs\": {
		\"detailed\": {
			\"image_path\": \"$image\"
		}
	}

}");

$headers = array();
$headers[] =  'Authorization: Bearer '.$token.'';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

curl_close($ch);


 \Log::info('result:' .$result); 



            $response =  json_decode($result) ;
     
     
        return $response;
    }

   
  public function curl_update_product($business_id,$product)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    
        $id = $product['id'];
        $image = $product['images'];
        $categories = json_encode($product['categories']);
        $category = $product['category'];
        $sku = $product['sku'];
        $description = $product['description'];
        $name = $product['name'];
        $stock_quantity = $product['stock_quantity'];
        $regular_price = $product['regular_price']; 
        
     /*	\"full_description\": \"$description\",
	\"short_description\": \"$description\",*/
// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/2.0/products/'.$id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

    if(!empty($image)){
        
          curl_setopt($ch, CURLOPT_POSTFIELDS, "{	
      \"lang_code\":\"ar\",
	\"product\": \"$name\",

	\"category_ids\": $categories,
	\"main_category\": $category,
	\"price\": $regular_price,
	\"amount\": $stock_quantity,

	\"product_code\": \"$sku\",
	\"company_id\": 1,
	\"status\": \"A\",
	\"main_pair\": {
		\"detailed\": {
			\"image_path\": \"$image\"
		}
	},
	\"image_pairs\": {
		\"detailed\": {
			\"image_path\": \"$image\"
		}
	}

}"); 
    }else{ 
        
          
     curl_setopt($ch, CURLOPT_POSTFIELDS, "{
     \"lang_code\":\"ar\",
	\"product\": \"$name\",

	\"category_ids\": $categories,
	\"main_category\": $category,
	\"price\": $regular_price,
	\"amount\": $stock_quantity,

	\"product_code\": \"$sku\",
	\"company_id\": 1,
	\"status\": \"A\"


}");  
             
        
    }
       
 
 
 



$headers = array();
$headers[] =  'Authorization: Bearer '.$token.'';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

curl_close($ch);
/* $re =  json_encode([$name,$sku,$id,$category,$categories,$regular_price,$stock_quantity]) ;
  \Log::info($re);
  \Log::info($result);*/

   

            $response =  json_decode($result) ;
     
     
        return $response;
    }





   
  public function curl_createVariationTemplate($business_id,$attr)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    
        $values = [];
        $name = $attr->name;
        $color = $attr->color;
        
      foreach($attr->values as $value){
          $var_name = $value->name ;
          $values[] = [
              "variant" => $var_name ,
              "color" => $color ,
              
              ] ;
          
      }  
      
      $json = json_encode($values) ;
   /* "{\"variant\": \"$var_name\",\"color\": \"$color\",},"*/
// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/features/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

curl_setopt($ch, CURLOPT_POSTFIELDS, "{
	\"feature_type\": \"S\",
	\"purpose\": \"group_variation_catalog_item\",
	\"description\": \"$name\",
	\"company_id\": 1,
	\"feature_style\": \"dropdown_labels\",
	\"status\": \"A\",
    \"variants\": $json
      
     

}");

$headers = array();
$headers[] =  'Authorization: Bearer '.$token.'';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

curl_close($ch);


    \Log::info($result);


            $response =  json_decode($result) ;
     
   
        return $response;
    }


    public function curl_getVariationTemplate($business_id,$id)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    

   /* "{\"variant\": \"$var_name\",\"color\": \"$color\",},"*/
// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/features/'.$id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


$headers = array();
$headers[] =  'Authorization: Bearer '.$token.'';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

curl_close($ch);


    \Log::info($result);


            $response =  json_decode($result) ;
     
     
        return $response;
    }


    public function curl_updateVariationTemplate($business_id,$attr)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    
        $values = [];
        $name = $attr->name;
        $color = $attr->color;
        
      foreach($attr->values as $value){
          $var_name = $value->name ;
          $values[] = [
              "variant" => $var_name ,
              "color" => $color ,
              
              ] ;
          
      }  
      
      $json = json_encode($values) ;
   /* "{\"variant\": \"$var_name\",\"color\": \"$color\",},"*/
// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/features/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

curl_setopt($ch, CURLOPT_POSTFIELDS, "{
	\"feature_type\": \"S\",
	\"purpose\": \"group_variation_catalog_item\",
	\"description\": \"$name\",
	\"company_id\": 1,
	\"feature_style\": \"dropdown_labels\",
	\"status\": \"A\",
    \"variants\": $json
      
     

}");

$headers = array();
$headers[] =  'Authorization: Bearer '.$token.'';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);

curl_close($ch);


    \Log::info($result);


            $response =  json_decode($result) ;
     
   
        return $response;
    }

  
  /*----------------------------------------------------------------------------------------------------------------------*/
  
  //orders
  
      public function getOrders($business_id)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    


        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/orders/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        
        
        $headers = array();
        $headers[] =  'Authorization: Bearer '.$token.'';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        
        curl_close($ch);


    \Log::info($result);


            $response =  json_decode($result) ;
     
     
        return $response->orders;
    }

  
      public function getSingleOrder($business_id,$id)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    


        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/orders/'.$id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        
        
        $headers = array();
        $headers[] =  'Authorization: Bearer '.$token.'';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        
        curl_close($ch);


    \Log::info($result);


            $response =  json_decode($result) ;
     
     
        return $response;
    }

  
  
  //user
      public function getUser($business_id,$id)
    {
        $cscart_api_settings = $this->get_api_settings($business_id);
        if (empty($cscart_api_settings)) {
            throw new cscartError(__("cscart::lang.unable_to_connect"));
        }
        
      
    
        

        $email =  $cscart_api_settings->cscart_consumer_key;
        $api_key =  $cscart_api_settings->cscart_consumer_secret;

        $token = $email.":".$api_key;

        $token = base64_encode($token);
    


        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $cscart_api_settings->cscart_app_url.'/api/users/'.$id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        
        
        $headers = array();
        $headers[] =  'Authorization: Bearer '.$token.'';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        
        curl_close($ch);


    \Log::info($result);


            $response =  json_decode($result) ;
     
     
        return $response;
    }

  
  
}