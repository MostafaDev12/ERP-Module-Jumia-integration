<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-12">
            <h4>@lang('cscart::lang.order_created')</h4>
        </div>
    	<div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('cscart_wh_oc_secret',  __('cscart::lang.webhook_secret') . ':') !!}
            	{!! Form::text('cscart_wh_oc_secret', !empty($business->cscart_wh_oc_secret) ? $business->cscart_wh_oc_secret : null, ['class' => 'form-control','placeholder' => __('cscart::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('cscart::lang.webhook_delivery_url'):</strong>
                <p>{{action('\Modules\Cscart\Http\Controllers\CscartWebhookController@orderCreated', ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

        <div class="col-xs-12">
            <h4>@lang('cscart::lang.order_updated')</h4>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('cscart_wh_ou_secret',  __('cscart::lang.webhook_secret') . ':') !!}
                {!! Form::text('cscart_wh_ou_secret', !empty($business->cscart_wh_oc_secret) ? $business->cscart_wh_ou_secret : null, ['class' => 'form-control','placeholder' => __('cscart::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('cscart::lang.webhook_delivery_url'):</strong>
                <p>{{action('\Modules\Cscart\Http\Controllers\CscartWebhookController@orderUpdated', ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

        <div class="col-xs-12">
            <h4>@lang('cscart::lang.order_deleted')</h4>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('cscart_wh_od_secret',  __('cscart::lang.webhook_secret') . ':') !!}
                {!! Form::text('cscart_wh_od_secret', !empty($business->cscart_wh_oc_secret) ? $business->cscart_wh_od_secret : null, ['class' => 'form-control','placeholder' => __('cscart::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('cscart::lang.webhook_delivery_url'):</strong>
                <p>{{action('\Modules\Cscart\Http\Controllers\CscartWebhookController@orderDeleted', ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

        <div class="col-xs-12">
            <h4>@lang('cscart::lang.order_restored')</h4>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('cscart_wh_or_secret',  __('cscart::lang.webhook_secret') . ':') !!}
                {!! Form::text('cscart_wh_or_secret', !empty($business->cscart_wh_oc_secret) ? $business->cscart_wh_or_secret : null, ['class' => 'form-control','placeholder' => __('cscart::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('cscart::lang.webhook_delivery_url'):</strong>
                <p>{{action('\Modules\Cscart\Http\Controllers\CscartWebhookController@orderRestored', ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

    </div>
</div>