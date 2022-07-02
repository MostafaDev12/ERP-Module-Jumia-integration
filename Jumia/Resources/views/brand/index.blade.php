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
            <h4><i class="fas fa-tags"></i> {{__( 'brand.all_your_brands' )}}</h4>
            
        </div>
        <div class="default-box-body">
            @can('brand.view')
                <div class="table-responsive">
                    <table class="main_light_table table table-bordered table-striped" id="brand_table">
                        <thead>
                            <tr>
                                <th>@lang( 'brand.brands' )</th>
                                <th>@lang( 'jumia::layouts.jumia_brand' )</th>
                                <th>@lang( 'messages.action' )</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcan
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
       var brands_table = $('#brand_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{action('\Modules\Jumia\Http\Controllers\BrandController@index')}}",
        columnDefs: [
            {
                targets: 2,
                orderable: false,
                searchable: false,
            },
        ],
    });
    
    
     $(document).on('click', 'button.edit_brand_button', function() {
        $('div.brands_modal').load($(this).data('href'), function() {
            $(this).modal('show');

         $('div.brands_modal')
            .find('.select2')
            .each(function() {
                __select2($(this));
                __select2($('.select2'));
            });
            $('form#brand_edit_form').submit(function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.brands_modal').modal('hide');
                            toastr.success(result.msg);
                            brands_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });
</script>
@stop

