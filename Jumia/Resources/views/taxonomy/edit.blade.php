<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\Jumia\Http\Controllers\TaxonomyController@update', [$category->id]), 'method' => 'PUT', 'id' => 'category_edit_form', 'class' => 'add-product-form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'jumia::lang.jumia_category' )</h4>
    </div>

    <div class="modal-body">
    
    
          <div class="mb-3 ">
            {!! Form::label('jumia_cat_id', __( 'jumia::lang.jumia_category' ) . ':') !!}
           <!-- {!! Form::select('jumia_cat_id', $jCategories, $category->jumia_cat_id, ['class' => 'form-control select2']); !!}-->
              <select class="form-control select2" style="width:100%" id="jumia_cat_id" name="jumia_cat_id">
               <option value="" >{{__('lang_v1.none')}}</option>
              @foreach($jCategories as $key=>$jbrand)
              <option value="{{$key}}" {{$jbrand == $category->jumia_cat_id ? 'selected': ''}}>{{$jbrand}}</option>
              @endforeach
          </select>
          </div>
     
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn main-bg-dark text-white">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-secondary text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->