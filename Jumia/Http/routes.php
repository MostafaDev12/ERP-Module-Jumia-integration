<?php

Route::post(
    '/jumia/webhook/order-created/{business_id}',
    'Modules\Jumia\Http\Controllers\CscartWebhookController@orderCreated'
);
Route::post(
    '/jumia/webhook/order-updated/{business_id}',
    'Modules\Jumia\Http\Controllers\CscartWebhookController@orderUpdated'
);
Route::post(
    '/jumia/webhook/order-deleted/{business_id}',
    'Modules\Jumia\Http\Controllers\CscartWebhookController@orderDeleted'
);
Route::post(
    '/jumia/webhook/order-restored/{business_id}',
    'Modules\Jumia\Http\Controllers\CscartWebhookController@orderRestored'
);

Route::group(['middleware' => ['web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu'], 'prefix' => 'jumia', 'namespace' => 'Modules\Jumia\Http\Controllers'], function () {
    Route::get('/install', 'InstallController@index');
    Route::get('/install/update', 'InstallController@update');
    Route::get('/install/uninstall', 'InstallController@uninstall');
    
    Route::get('/', 'JumiaController@index');
    Route::get('/api-settings', 'JumiaController@apiSettings');
    Route::post('/update-api-settings', 'JumiaController@updateSettings');
    Route::get('/sync-categories', 'JumiaController@syncCategories');
    Route::get('/sync-products', 'JumiaController@syncProducts');
    Route::get('/sync-stocks', 'JumiaController@syncStocks');
    Route::get('/sync-log', 'JumiaController@getSyncLog');
    Route::get('/sync-orders', 'JumiaController@syncOrders');
    Route::post('/map-taxrates', 'JumiaController@mapTaxRates');
    Route::get('/view-sync-log', 'JumiaController@viewSyncLog');
    Route::get('/get-log-details/{id}', 'JumiaController@getLogDetails');
    Route::get('/reset-categories', 'JumiaController@resetCategories');
    Route::get('/reset-products', 'JumiaController@resetProducts');
    
    Route::resource('brands', 'BrandController');
      Route::get('/brands/edit2/{id}', 'BrandController@edit2');
    Route::PUT('/brands/update2/{id}', 'BrandController@update2');
    
        Route::resource('taxonomies', 'TaxonomyController');
});
