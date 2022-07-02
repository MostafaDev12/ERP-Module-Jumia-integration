@if($__is_cs_enabled)
	@if(auth()->user()->can('superadmin') || auth()->user()->can('cscart.syc_categories') || auth()->user()->can('cscart.sync_products') || auth()->user()->can('cscart.sync_orders') || auth()->user()->can('cscart.map_tax_rates') || auth()->user()->can('cscart.access_cscart_api_settings'))
		<li class="bg-woocommerce treeview {{ in_array($request->segment(1), ['cscart']) ? 'active active-sub' : '' }}">
		    <a href="#">
		        <i class="fa fa-opencart"></i>
		        <span class="title">@lang('cscart::lang.cscart')</span>
		        <span class="pull-right-container">
		            <i class="fa fa-angle-left pull-right"></i>
		        </span>
		    </a>

		    <ul class="treeview-menu">
		    	<li class="{{ $request->segment(1) == 'cscart' && empty($request->segment(2)) ? 'active active-sub' : '' }}">
					<a href="{{action('\Modules\Cscart\Http\Controllers\CscartController@index')}}">
						<i class="fa fa-refresh"></i>
						<span class="title">
							@lang('cscart::lang.sync')
						</span>
				  	</a>
				</li>
				<li class="{{ $request->segment(1) == 'cscart' && $request->segment(2) == 'view-sync-log' ? 'active active-sub' : '' }}">
					<a href="{{action('\Modules\Cscart\Http\Controllers\CscartController@viewSyncLog')}}">
						<i class="fa fa-history"></i>
						<span class="title">
							@lang('cscart::lang.sync_log')
						</span>
				  	</a>
				</li>
				@if(auth()->user()->can('cscart.access_cscart_api_settings') )
				<li class="{{ $request->segment(1) == 'cscart' && $request->segment(2) == 'api-settings' ? 'active active-sub' : '' }}">
					<a href="{{action('\Modules\Cscart\Http\Controllers\CscartController@apiSettings')}}">
						<i class="fa fa-cogs"></i>
						<span class="title">
							@lang('cscart::lang.api_settings')
						</span>
				  	</a>
				</li>
				@endif
	        </ul>
		</li>
	@endif
@endif