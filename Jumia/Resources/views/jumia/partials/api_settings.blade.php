<div class="pos-tab-content">
    <div class="row">
    	<div class="col-lg-4 col-md-6">
            <div class="form-group">
            	{!! Form::label('jumia_app_url',  __('jumia::lang.jumia_app_url') . ':') !!}
            	{!! Form::text('jumia_app_url', $default_settings['jumia_app_url'], ['class' => 'form-control','placeholder' => __('jumia::lang.jumia_app_url')]); !!}
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="form-group">
                {!! Form::label('jumia_consumer_key',  __('jumia::lang.email') . ':') !!}
                {!! Form::text('jumia_consumer_key', $default_settings['jumia_consumer_key'], ['class' => 'form-control','placeholder' => __('jumia::lang.email')]); !!}
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="form-group">
            	{!! Form::label('jumia_consumer_secret', __('jumia::lang.api_key') . ':') !!}
                <input type="password" name="jumia_consumer_secret" value="{{$default_settings['jumia_consumer_secret']}}" id="jumia_consumer_secret" class="form-control">
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-lg-4 col-md-6">
            <div class="form-group">
                {!! Form::label('location_id',  __('business.business_locations') . ':') !!} @show_tooltip(__('jumia::lang.location_dropdown_help'))
                {!! Form::select('location_id', $locations, $default_settings['location_id'], ['class' => 'form-select']); !!}
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="checkbox">
                <label>
                    <br/>
                    {!! Form::checkbox('enable_auto_sync', 1, !empty($default_settings['enable_auto_sync']), ['class' => 'input-icheck'] ); !!} @lang('jumia::lang.enable_auto_sync')
                </label>
                @show_tooltip(__('jumia::lang.auto_sync_tooltip'))
            </div>
        </div>
    </div>
</div>