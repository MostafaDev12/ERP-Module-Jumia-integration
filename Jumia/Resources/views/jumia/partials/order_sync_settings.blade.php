<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-12">
        	@php
        		$pos_sell_statuses = [
        			'final' => __('lang_v1.final'),
        			'draft' => __('sale.draft'),
        			'quotation' => __('lang_v1.quotation')
        		];

        		$woo_order_statuses = [
        			'pending' => __('jumia::lang.pending'),
        			'shipped' => __('jumia::lang.shipped'),
        			'processing' => __('jumia::lang.processing'),
        			'ready_to_ship' => __('jumia::lang.ready_to_ship'),
        			'return_rejected' => __('jumia::lang.return_rejected'),
        			'return_shipped_by_customer' => __('jumia::lang.return_shipped_by_customer'),
        			'return_waiting_for_approval' => __('jumia::lang.return_waiting_for_approval'),
        			'failed' => __('jumia::lang.failed'),
        			'returned' => __('jumia::lang.has been returned'),
        		 
        			'canceled' => __('jumia::lang.has been canceled'),
        		
        			'delivered' => __('jumia::lang.has been completed')
        		
        		
        		];

        	@endphp
        	<div class="table-responsive">
        	    <table class="table">
            		<tr>
            			<th>@lang('jumia::lang.jumia_order_status')</th>
            			<th>@lang('jumia::lang.equivalent_pos_sell_status')</th>
                        <th>@lang('jumia::lang.equivalent_shipping_status')</th>
            		</tr>
            		@foreach($woo_order_statuses as $key => $value)
            		<tr>
            			<td>
            				{{$value}}
            			</td>
            			<td>
            				{!! Form::select("order_statuses[$key]", $pos_sell_statuses, $default_settings['order_statuses'][$key] ?? null, ['class' => 'form-select select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]); !!}
            			</td>
                        <td>
                            {!! Form::select("shipping_statuses[$key]", $shipping_statuses, $default_settings['shipping_statuses'][$key] ?? null, ['class' => 'form-select select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]); !!}
                        </td>
            		</tr>
            		@endforeach
            	</table>
        	</div>
        </div>
    </div>
</div>