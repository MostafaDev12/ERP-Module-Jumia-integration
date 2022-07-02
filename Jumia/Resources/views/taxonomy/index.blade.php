@extends('layouts.app')
@php
    $heading = !empty($module_category_data['heading']) ? $module_category_data['heading'] : __('category.categories');
@endphp
@section('title', $heading)

@section('content')

@include('jumia::layouts.nav')
<!-- Content Header (Page header) -->
 
<!-- Main content -->
<section>
    @php
        $cat_code_enabled = isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code'] ? false : true;
    @endphp
    <input type="hidden" id="category_type" value="{{request()->get('type')}}">
    {{--@component('components.widget', ['class' => 'box-primary'])--}}
    <div class="default-box">
        <div class="default-box-head d-flex align-items-center justify-content-between">
            <h4><i class="fas fa-sitemap"></i> {{$heading}}</h4>
           
        </div>
        <div class="default-box-body">
            @can('category.view')
                <div class="table-responsive">
                    <table class="main_light_table table table-bordered table-striped" id="category_table">
                        <thead>
                            <tr>
                                <th> @lang( 'category.category' )</th>
                               
                                <th>@lang( 'jumia::lang.jumia_category' )</th>
                                <th>@lang( 'messages.action' )</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcan
        </div>
    </div>
    {{--@endcomponent--}}

    <div class="modal fade category_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
@stop
@section('javascript')

<script type="text/javascript">
    $(document).ready( function() {
        //Category table
        if ($('#category_table').length) {  
            var category_type = $('#category_type').val();
            category_table = $('#category_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/jumia/taxonomies?type=' + category_type,
                columns: [
                    { data: 'name', name: 'name' },
                   
                    { data: 'jumia_cat_id', name: 'jumia_cat_id' },
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                ],
            });
      }
    });
    $(document).on('submit', 'form#category_add_form', function(e) {
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
                if (result.success === true) {
                    $('div.category_modal').modal('hide');
                    toastr.success(result.msg);
                    category_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', 'button.edit_category_button', function() {
        $('div.category_modal').load($(this).data('href'), function() {
            $(this).modal('show');

  $('div.category_modal')
            .find('.select2')
            .each(function() {
                __select2($(this));
                __select2($('.select2'));
            });

            $('form#category_edit_form').submit(function(e) {
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
                        if (result.success === true) {
                            $('div.category_modal').modal('hide');
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });
    });

    $(document).on('click', 'button.delete_category_button', function() {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_category,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            category_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
</script>

@endsection
