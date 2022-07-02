<div class="d-flex align-items-top justify-content-between no-print">
    <nav class="navbar navbar-expand-lg bg-white mb-4 p-0 inner-nav">
        <div class="container-fluid p-0">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <i class="fas fa-list"></i>
            </button>
            <div class="collapse navbar-collapse px-0 mx-0" id="bs-example-navbar-collapse-1">
                <div class="navbar-nav">
                    <a href="{{action('\Modules\Jumia\Http\Controllers\JumiaController@index')}}" @if(request()->segment(1) == 'jumia' && request()->segment(2) == null) class="active-dark nav-link" @else class="nav-link" @endif>{{__('jumia::lang.jumia')}}</a>
                    <a href="{{action('\Modules\Jumia\Http\Controllers\JumiaController@viewSyncLog')}}" @if(request()->segment(1) == 'jumia' && request()->segment(2) == 'view-sync-log') class="active-dark nav-link" @else class="nav-link" @endif>@lang('jumia::lang.sync_log')</a>
                     <a href="{{action('\Modules\Jumia\Http\Controllers\BrandController@index')}}" @if(request()->segment(1) == 'jumia' && request()->segment(2) == 'brands') class="active-dark nav-link" @else class="nav-link" @endif>@lang('jumia::lang.brands')</a>
                     <a href="{{action('\Modules\Jumia\Http\Controllers\TaxonomyController@index') . '?type=product'}}" @if(request()->segment(1) == 'jumia' && request()->segment(2) == 'taxonomies') class="active-dark nav-link" @else class="nav-link" @endif>@lang('jumia::lang.categories')</a>
                    @if (auth()->user()->can('jumia.access_jumia_api_settings'))
                        <a href="{{action('\Modules\Jumia\Http\Controllers\JumiaController@apiSettings')}}" @if(request()->segment(1) == 'jumia' && request()->segment(2) == 'api-settings') class="active-dark nav-link" @else class="nav-link" @endif>@lang('jumia::lang.api_settings')</a>
                    @endif
                </div>
            </div>
        </div>
    </nav>
</div>
{{--
<section class="no-print">
    <nav class="navbar navbar-default bg-white m-4">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="{{action('\Modules\Jumia\Http\Controllers\JumiaController@index')}}"><i class="fab fa-opencart"></i> {{__('jumia::lang.jumia')}}</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li @if(request()->segment(1) == 'jumia' && request()->segment(2) == 'view-sync-log') class="active" @endif><a href="{{action('\Modules\Jumia\Http\Controllers\JumiaController@viewSyncLog')}}">@lang('jumia::lang.sync_log')</a></li>

                    @if (auth()->user()->can('jumia.access_jumia_api_settings'))
                        <li @if(request()->segment(1) == 'jumia' && request()->segment(2) == 'api-settings') class="active" @endif><a href="{{action('\Modules\Jumia\Http\Controllers\JumiaController@apiSettings')}}">@lang('jumia::lang.api_settings')</a></li>
                    @endif
                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>
--}}