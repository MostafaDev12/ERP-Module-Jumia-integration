@extends('layouts.app')
@section('title', __('jumia::lang.jumia'))

@section('content')
@include('jumia::layouts.nav')
{{--
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('jumia::lang.jumia')</h1>
</section>
--}}

<!-- Main content -->
<section class="content">
    @php
        $is_superadmin = auth()->user()->can('superadmin');
    @endphp
    <div class="row">
        @if(!empty($alerts['connection_failed']))
        <div class="col-sm-12">
            <div class="alert alert-danger alert-dismissible mb-4 ps-4">
                <button type="button" class="btn-close h-100 py-0 pe-4" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
                <ul>
                    <li>{{$alerts['connection_failed']}}</li>
                </ul>
            </div>
        </div>
        @endif

     
        <div class="col-lg-12">
            @if($is_superadmin || auth()->user()->can('jumia.sync_products') )
            <div class="col-sm-6">
                <div class="hrm-box mb-5">
    		        <div class="hrm-box-head bb-0">
    		            <h4><img src="{{asset('new_assets/images/apps/packet.png')}}" class="me-3"> @lang('jumia::lang.sync_products'):</h4>
    		        </div>
    		        <div class="hrm-box-body">
    		            @if(!empty($alerts['not_synced_product']) || !empty($alerts['not_updated_product']))
                            <div class="alert alert-warning alert-dismissible mb-0 ps-4">
                                <button type="button" class="btn-close h-100 py-0 pe-4" data-bs-dismiss="alert" data-dismiss="alert" aria-label="Close"></button>
                                <ul>
                                    @if(!empty($alerts['not_synced_product']))
                                        <li>{{$alerts['not_synced_product']}}</li>
                                    @endif
                                    @if(!empty($alerts['not_updated_product']))
                                        <li>{{$alerts['not_updated_product']}}</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                        <div class="clearfix"></div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-5">
                            <div>
                                <button type="button" class="btn main-bg-dark text-white sync_products" data-sync-type="new"> <i class="fas fa-sync me-2"></i> @lang('jumia::lang.sync_new_only')</button>
                              <!--  &nbsp;@show_tooltip(__('jumia::lang.sync_new_help'))
                                <span class="last_sync_new_products"></span>-->
                            </div> 
                            <div>
                                <button type="button" class="btn main-bg-dark text-white sync_products" data-sync-type="update"> <i class="fas fa-sync me-2"></i> @lang('jumia::lang.sync_only_update')</button>
                              <!--  &nbsp;@show_tooltip(__('jumia::lang.sync_update_help'))
                                <span class="last_sync_update_products"></span>-->
                            </div>
                            <div>
                                <button type="button" class="btn main-bg-light text-white sync_products" data-sync-type="all"> <i class="fas fa-sync me-2"></i> @lang('jumia::lang.sync_all')</button>
                                &nbsp;@show_tooltip(__('jumia::lang.sync_all_help'))
                              
                            </div> 
                            <span class="last_sync_all_products"></span>
                            <div class="mt-3 hide">
                                <button type="button" class="btn btn-danger" id="reset_products"> <i class="fas fa-redo me-2"></i> @lang('jumia::lang.reset_synced_products')</button>
                            </div>
                            <br>
                             <div style="width: 100%;padding-top: 10px;">
                                <button type="button" class="btn main-bg-dark text-white sync_stocks" data-sync-type="update" style="width: 100%;"> <i class="fas fa-sync me-2" ></i> @lang('jumia::lang.sync_all_stocks')</button>
                           
                            </div>
                        </div>
                    </div>
               </div>
           </div>
           @endif
           @if($is_superadmin || auth()->user()->can('jumia.sync_orders') )
            <div class="col-sm-6">
                <div class="hrm-box mb-5">
    		        <div class="hrm-box-head bb-0">
    		            <h4><img src="{{asset('new_assets/images/apps/purchases.png')}}" class="me-3"> @lang('jumia::lang.sync_orders'):</h4>
    		        </div>
    		        <div class="hrm-box-body">
    		            <button type="button" class="btn btn-success" id="sync_orders"> <i class="fas fa-sync me-2"></i> @lang('jumia::lang.sync')</button>
                        <span class="last_sync_orders"></span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    
</section>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready( function() {
        syncing_text = '<i class="fas fa-sync me-2"></i> ' + "{{__('jumia::lang.syncing')}}...";
        update_sync_date();

        //Sync Product Categories
        $('#sync_product_categories').click( function(){
            $(window).bind('beforeunload', function(){
                return true;
            });
            var btn_html = $(this).html(); 
            $(this).html(syncing_text); 
            $(this).attr('disabled', true);
            $.ajax({
                url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@syncCategories')}}",
                dataType: "json",
                timeout: 0,
                success: function(result){
                    if(result.success){
                        toastr.success(result.msg);
                        update_sync_date();
                        console.log(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                    $('#sync_product_categories').html(btn_html);
                    $('#sync_product_categories').removeAttr('disabled');
                    $(window).unbind('beforeunload');
                }
            });          
        });

        //Sync Products
        $('.sync_products').click( function(){
            $(window).bind('beforeunload', function(){
                return true;
            });
            var btn = $(this);
            var btn_html = btn.html();
            btn.html(syncing_text); 
            btn.attr('disabled', true);

            sync_products(btn, btn_html);     
        });

        //Sync Orders
        $('#sync_orders').click( function(){
            $(window).bind('beforeunload', function(){
                return true;
            });
            var btn = $(this);
            var btn_html = btn.html(); 
            btn.html(syncing_text); 
            btn.attr('disabled', true);

            $.ajax({
                url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@syncOrders')}}",
                dataType: "json",
                timeout: 0,
                success: function(result){
                    if(result.success){
                        toastr.success(result.msg);
                        update_sync_date();
                    } else {
                        toastr.error(result.msg);
                    }
                    btn.html(btn_html);
                    btn.removeAttr('disabled');
                    $(window).unbind('beforeunload');
                }
            });            
        });
    });

    function update_sync_date() {
        $.ajax({
            url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@getSyncLog')}}",
            dataType: "json",
            timeout: 0,
            success: function(data){
                if(data.categories){
                    $('span.last_sync_cat').html('<small>{{__("jumia::lang.last_synced")}}: ' + data.categories + '</small>');
                }
                if(data.new_products){
                    $('span.last_sync_new_products').html('<small>{{__("jumia::lang.last_synced")}}: ' + data.new_products + '</small>');
                }  
                if(data.update_products){
                    $('span.last_sync_update_products').html('<small>{{__("jumia::lang.last_synced")}}: ' + data.update_products + '</small>');
                }
                if(data.all_products){
                    $('span.last_sync_all_products').html('<small>{{__("jumia::lang.last_synced")}}: ' + data.all_products + '</small>');
                }
                if(data.orders){
                    $('span.last_sync_orders').html('<small>{{__("jumia::lang.last_synced")}}: ' + data.orders + '</small>');
                }
                
            }
        });     
    }

    //Reset Synced Categories
    $(document).on('click', 'button#reset_categories', function(){
        var checkbox = document.createElement("div");
        checkbox.setAttribute('class', 'checkbox');
        checkbox.innerHTML = '<label><input type="checkbox" id="yes_reset_cat"> {{__("jumia::lang.yes_reset")}}</label>';
        swal({
          title: LANG.sure,
          text: "{{__('jumia::lang.confirm_reset_cat')}}",
          icon: "warning",
          content: checkbox,
          buttons: true,
          dangerMode: true,
        }).then((confirm) => {
            if(confirm) {
                if($('#yes_reset_cat').is(":checked")) {
                    $(window).bind('beforeunload', function(){
                        return true;
                    });
                    var btn = $(this);
                    btn.attr('disabled', true);
                    $.ajax({
                        url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@resetCategories')}}",
                        dataType: "json",
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            btn.removeAttr('disabled');
                            $(window).unbind('beforeunload');
                            location.reload();
                        }
                    });
                }
            }
        });
    });

    //Reset Synced products
    $(document).on('click', 'button#reset_products', function(){
        var checkbox = document.createElement("div");
        checkbox.setAttribute('class', 'checkbox');
        checkbox.innerHTML = '<label><input type="checkbox" id="yes_reset_product"> {{__("jumia::lang.yes_reset")}}</label>';
        swal({
          title: LANG.sure,
          text: "{{__('jumia::lang.confirm_reset_product')}}",
          icon: "warning",
          content: checkbox,
          buttons: true,
          dangerMode: true,
        }).then((confirm) => {
            if(confirm) {
                if($('#yes_reset_product').is(":checked")) {
                    $(window).bind('beforeunload', function(){
                        return true;
                    });
                    var btn = $(this);
                    btn.attr('disabled', true);
                    $.ajax({
                        url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@resetProducts')}}",
                        dataType: "json",
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            btn.removeAttr('disabled');
                            $(window).unbind('beforeunload');
                            location.reload();
                        }
                    });
                }
            }
        });
    });

    function sync_products(btn, btn_html, offset = 0) {
        var type = btn.data('sync-type');
        $.ajax({
            url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@syncProducts')}}?type=" + type + "&offset=" + offset,
            dataType: "json",
            timeout: 0,
            success: function(result){
                if(result.success){
                    if (result.total_products > 0) {
                        offset++;
                        sync_products(btn, btn_html, offset)
                    } else {
                        update_sync_date();
                        btn.html(btn_html);
                        btn.removeAttr('disabled');
                        $(window).unbind('beforeunload');
                    }
                    toastr.success(result.msg);
                    
                } else {
                    toastr.error(result.msg);
                    btn.html(btn_html);
                    btn.removeAttr('disabled');
                    $(window).unbind('beforeunload');
                }
            }
        });     
    }
    
    
    
    
      //Sync Stocks
        $('.sync_stocks').click( function(){
            $(window).bind('beforeunload', function(){
                return true;
            });
            var btn = $(this);
            var btn_html = btn.html();
            btn.html(syncing_text); 
            btn.attr('disabled', true);

            sync_stocks(btn, btn_html);     
        });
    
    function sync_stocks(btn, btn_html, offset = 0) {
        var type = btn.data('sync-type');
        $.ajax({
            url: "{{action('\Modules\Jumia\Http\Controllers\JumiaController@syncStocks')}}?type=" + type + "&offset=" + offset,
            dataType: "json",
            timeout: 0,
            success: function(result){
                if(result.success){
                    if (result.total_products > 0) {
                        offset++;
                        sync_stocks(btn, btn_html, offset)
                    } else {
                        update_sync_date();
                        btn.html(btn_html);
                        btn.removeAttr('disabled');
                        $(window).unbind('beforeunload');
                    }
                    toastr.success(result.msg);
                    
                } else {
                    toastr.error(result.msg);
                    btn.html(btn_html);
                    btn.removeAttr('disabled');
                    $(window).unbind('beforeunload');
                }
            }
        });     
    }

</script>
@endsection