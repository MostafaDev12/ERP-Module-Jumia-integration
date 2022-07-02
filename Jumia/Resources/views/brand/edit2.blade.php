<div class="modal-dialog" role="document">
  <div class="modal-content">

     {!! Form::open(['url' => action('\Modules\Jumia\Http\Controllers\BrandController@update2', [$brand->id]), 'method' => 'PUT', 'id' => 'brand_edit_form', 'class' => 'add-product-form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title"> {{__( 'jumia::lang.jumia_brands' )}}</h4>
    </div>

    <div class="modal-body">
      <div class="mb-3">
        {!! Form::label('jumia_brand', __( 'jumia::lang.jumia_brands' ) . ':*') !!}
          {!! Form::text('jumia_brand', $brand->jumia_brand, ['class' => 'form-control', 'required', 'placeholder' => __( 'jumia::lang.jumia_brands' ) ]); !!}
      </div>

    
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn main-bg-dark text-white">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-secondary text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->