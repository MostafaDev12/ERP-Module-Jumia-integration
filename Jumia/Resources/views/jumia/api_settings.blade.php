@extends('layouts.app')
@section('title', __('jumia::lang.api_settings'))

@section('content')
@include('jumia::layouts.nav')
{{--
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('jumia::lang.api_settings')</h1>
</section>
--}}
<!-- Main content -->
<section class="content">
    {!! Form::open(['action' => '\Modules\Jumia\Http\Controllers\JumiaController@updateSettings', 'method' => 'post']) !!}
    <div class="default-box p-0">
        <div class="default-box-body">
            <div class="row">
                <div class="col-md-3 col-4 pos-tab-menu">
                    <div class="list-group list-settings-group">
                        <a href="#" class="list-settings-item text-center active">@lang('jumia::lang.instructions')</a>
                        <a href="#" class="list-settings-item text-center">@lang('jumia::lang.api_settings')</a>
                        <a href="#" class="list-settings-item text-center">@lang('jumia::lang.product_sync_settings')</a>
                        <a href="#" class="list-settings-item text-center">@lang('jumia::lang.order_sync_settings')</a>
                       <a href="#" class="list-settings-item text-center hide">@lang('jumia::lang.webhook_settings')</a>
                    </div>
                </div>
                <div class="col-md-9 col-8 pos-tab">
                    @include('jumia::jumia.partials.api_instructions')
                    @include('jumia::jumia.partials.api_settings')
                    @include('jumia::jumia.partials.product_sync_settings')
                    @include('jumia::jumia.partials.order_sync_settings')
                    @include('jumia::jumia.partials.webhook_settings')
                </div>
            </div>
<!--  </pos-tab-container> 
            <div class="col-xs-12">
                <p class="help-block"><i>{!! __('jumia::lang.version_info', ['version' => $module_version]) !!}</i></p>
            </div>
            <!--  </pos-tab-container> -->
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 text-end">
            {{Form::submit('update', ['class'=>"main-dark-btn-lg"])}}
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){
        $('.create_quantity').on('ifChecked', function(event){
            $('.create_stock_settings').each( function(){
                $(this).addClass('hide');
            });
        });
        $('.create_quantity').on('ifUnchecked', function(event){
            $('.create_stock_settings').each( function(){
                $(this).removeClass('hide');
            });
        });
        $('.update_quantity').on('ifChecked', function(event){
            $('.update_stock_settings').each( function(){
                $(this).addClass('hide');
            });
        });
        $('.update_quantity').on('ifUnchecked', function(event){
            $('.update_stock_settings').each( function(){
                $(this).removeClass('hide');
            });
        });
    });
</script>
@endsection