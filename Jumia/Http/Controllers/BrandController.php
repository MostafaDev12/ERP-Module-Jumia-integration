<?php

namespace Modules\Jumia\Http\Controllers;

use App\Brands;
use App\Arabian\BrandArabian;
use App\Cottonesta\BrandCottonesta;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controller;
use Modules\Jumia\Http\traits\ApiTrait;

class BrandController extends Controller
{
    /**
     * All Utils instance.
     *
     */
     
         use ApiTrait; 
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('brand.view') && !auth()->user()->can('brand.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $brands = Brands::where('business_id', $business_id)
                        ->select(['name', 'jumia_brand', 'id']);

            return Datatables::of($brands)
                ->addColumn(
                    'action',
                    '@can("brand.update")
                    <a href="{{action(\'\Modules\Jumia\Http\Controllers\BrandController@edit\', [$id])}}" class="btn btn-xs main-bg-light text-white "><i class="glyphicon glyphicon-edit"></i> @lang("jumia::lang.select_jumia_brand")</a>
                        &nbsp;   
                    <button data-href="{{action(\'\Modules\Jumia\Http\Controllers\BrandController@edit2\', [$id])}}" class="btn btn-xs main-bg-light text-white edit_brand_button"><i class="glyphicon glyphicon-edit"></i> @lang("jumia::lang.write_jumia_brand")</button>
                        &nbsp;
                    @endcan
                  '
                )
                ->removeColumn('id')
                ->rawColumns([2])
                ->make(false);
        }

        return view('jumia::brand.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
         
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('brand.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {}
            $jbrands = [];
            $business_id = request()->session()->get('user.business_id');
            $brand = Brands::where('business_id', $business_id)->find($id);
            
               $jumia = $this->woo_client($business_id,['Action' => 'GetBrands']);
               
             if(!empty($jumia->SuccessResponse)){
                 
              $jbrandss =  $jumia->SuccessResponse->Body->Brands->Brand;
              foreach($jbrandss as $jbrand){
                  
                  $jbrands[$jbrand->BrandId] = $jbrand->Name ;
                  
                  
              }
              
                 
             }
            

            return view('jumia::brand.edit')
                ->with(compact('brand','jbrands'));
        
    }
    
 public function edit2($id)
    {
        if (!auth()->user()->can('brand.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $jbrands = [];
            $business_id = request()->session()->get('user.business_id');
            $brand = Brands::where('business_id', $business_id)->find($id);
            
            
            

            return view('jumia::brand.edit2')
                ->with(compact('brand'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('brand.update')) {
            abort(403, 'Unauthorized action.');
        }

      
            try {
                $input = $request->only(['jumia_brand']);
                $business_id = $request->session()->get('user.business_id');

                $brand = Brands::where('business_id', $business_id)->findOrFail($id);
                $brand->jumia_brand = $input['jumia_brand'];
            
                $brand->save();
                
                $output = ['success' => true,
                            'msg' => __("brand.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }
  return redirect('/jumia/brands')->with('status', $output);
      //      return $output;
        if (request()->ajax()) {  }
    }
   public function update2(Request $request, $id)
    {
        if (!auth()->user()->can('brand.update')) {
            abort(403, 'Unauthorized action.');
        }

          if (request()->ajax()) {  
            try {
                $input = $request->only(['jumia_brand']);
                $business_id = $request->session()->get('user.business_id');

                $brand = Brands::where('business_id', $business_id)->findOrFail($id);
                $brand->jumia_brand = $input['jumia_brand'];
            
                $brand->save();
                
                $output = ['success' => true,
                            'msg' => __("brand.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }
  //  return redirect('/jumia/brands')->with('status', $output);
          return $output;
    }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {}

    
}
