@extends('layouts.app')
@section('title', 'Brands')

@section('content')

@include('jumia::layouts.nav')
<!-- Content Header (Page header) -->
{{--
<section class="content-header">
    <h1>@lang( 'brand.brands' )
        <small>@lang( 'brand.manage_your_brands' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>
--}}
<!-- Main content -->
<section class="content">
    {{--@component('components.widget', ['class' => 'box-primary', 'title' => __( 'brand.all_your_brands' )])--}}
    <div class="default-box">
        <div class="default-box-head d-flex align-items-center justify-content-between">
            <h4><i class="fas fa-tags"></i> {{__( 'jumia::lang.jumia_brands' )}}</h4>
            
        </div>
        <div class="default-box-body">
    {!! Form::open(['url' => action('\Modules\Jumia\Http\Controllers\BrandController@update', [$brand->id]), 'method' => 'PUT', 'id' => 'brand_edit_form', 'class' => 'add-product-form' ]) !!}

  
    <div class="modal-body">
      <div class="mb-3">
        {!! Form::label('jumia_brand', __( 'jumia::lang.jumia_brands' ) . ':*') !!}
          <select class="form-control select2" style="width:100%" id="jumia_brand" name="jumia_brand">
               <option value="" >{{__('lang_v1.none')}}</option>
              @foreach($jbrands as $jbrand)
              <option value="{{$jbrand}}" {{$jbrand == $brand->jumia_brand ? 'selected': ''}}>{{$jbrand}}</option>
              @endforeach
          </select>
        
        
      </div>

     
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn main-bg-dark text-white">@lang( 'messages.update' )</button>
    </div>

    {!! Form::close() !!}
   </div>
    </div>
    {{--@endcomponent--}}

    <div class="modal fade brands_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->



@endsection



@section('javascript')


<script>
      
    
     __select2($('.select2'));
   
</script>
@stop
