<?php

namespace Modules\Jumia\Http\Controllers;

use App\Category;
use App\Arabian\CatArabian;
use App\Arabian\SubArabian;

use App\Cottonesta\CatCottonesta;
use App\Cottonesta\SubCottonesta;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controller;
use Modules\Jumia\Http\traits\ApiTrait;

class TaxonomyController extends Controller
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
        if (!auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        $category_type = request()->get('type');
        

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $category = Category::where('business_id', $business_id)
                            ->where('category_type', 'product') 
                            ->where('parent_id', 0)
                            ->select(['name', 'jumia_cat_id', 'id', 'parent_id']);

            return Datatables::of($category)
                ->addColumn(
                    'action',
                    '@can("category.update")
                    <button data-href="{{action(\'\Modules\Jumia\Http\Controllers\TaxonomyController@edit\', [$id])}}?type=product" class="btn btn-xs main-bg-light text-white edit_category_button"><i class="glyphicon glyphicon-edit"></i>  @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                   '
                )
                ->editColumn('name', function ($row) {
                    if ($row->parent_id != 0) {
                        return '--' . $row->name;
                    } else {
                        return $row->name;
                    }
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns(['action'])
                ->make(true);
        }

      //  $module_category_data = $this->__getModuleData($category_type);

        return view('jumia::taxonomy.index');
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
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
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
        if (!auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $category = Category::where('business_id', $business_id)->find($id);
            
            $category_type = request()->get('type');
        //    $module_category_data = $this->__getModuleData($category_type);

            $parent_categories = Category::where('business_id', $business_id)
                                        ->where('parent_id', 0)
                                        ->where('category_type', 'product')
                                        ->where('id', '!=', $id)
                                        ->pluck('name', 'id');
            $is_parent = false;
            
            
            
            $jCategories = [];
            if ($category->parent_id == 0) {
                $is_parent = true;
                $selected_parent = null;
            } else {
                $selected_parent = $category->parent_id ;
            }

              $jumia = $this->woo_client($business_id,['Action' => 'GetCategoryTree']);
               
             if(!empty($jumia->SuccessResponse)){
                 
              $jCategoriess =  $jumia->SuccessResponse->Body->Categories->Category;
              foreach($jCategoriess as $jCategory){
                  
                    $jCategories[$jCategory->CategoryId] = $jCategory->Name ;
                    
                  if(!empty($jCategory->Children->Category)){
                    foreach($jCategory->Children->Category as $jchildren1){
                          if(!empty($jchildren1->CategoryId)){
                      $jCategories[$jchildren1->CategoryId] = $jchildren1->Name ;
                          }
                if(!empty($jchildren1->Children->Category)){
                    foreach($jchildren1->Children->Category as $jchildren2){
                        
                     if(!empty($jchildren2->CategoryId)){
                      $jCategories[$jchildren2->CategoryId] = $jchildren2->Name ;
                     }
                     
               if(!empty($jchildren2->Children->Category)){
                    foreach($jchildren2->Children->Category as $jchildren3){
                         if(!empty($jchildren3->CategoryId)){
                      $jCategories[$jchildren3->CategoryId] = $jchildren3->Name ;
                         }
                    }  
                      
                  }  /**/
                    }  
                      
                  }
                
                  
                      
                      
                      
                    }  
                      
                  }
                
                  
                  
              }
              
                 
             }
            


            return view('jumia::taxonomy.edit')
                ->with(compact('category', 'parent_categories', 'is_parent', 'selected_parent','jCategories'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                
              
            
                      $input = $request->only(['jumia_cat_id']);
                     
               
                
              
                
            //    $input = $request->only(['name', 'short_code', 'description']);
                
                $business_id = $request->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);
                $category->jumia_cat_id = $input['jumia_cat_id'];
            
                $category->save();

                $output = ['success' => true,
                            'msg' => __("category.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

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
    {
        if (!auth()->user()->can('category.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);
                
               
                $category->delete();
            /*    if($business_id == 1 && !empty($category->rand)){ 
                    
                 $deletecat = CatArabian::where('rand',$category->id)->first();
                  $deletesub = SubArabian::where('rand',$category->id)->first();
                 if(!empty($deletecat)){
                   
                    $deletecat->delete(); 
                 }elseif(!empty($deletesub)){
                  
                    $deletesub->delete(); 
                 }
                 
                
                 
                }     */   
                
          
                
                $output = ['success' => true,
                            'msg' => __("category.deleted_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    public function getCategoriesApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');

            $api_settings = $this->moduleUtil->getApiSettings($api_token);
            
            $categories = Category::catAndSubCategories($api_settings->business_id);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            return $this->respondWentWrong($e);
        }

        return $this->respond($categories);
    }


    /**
     * Validate module category types and
     * return module category data if validates
     *
     * @param  string  $category_type
     * @return array
     */
    private function __getModuleData($category_type)
    {
        $category_types = ['product'];

        $modules_data = $this->moduleUtil->getModuleData('addTaxonomies');
        $module_data = [];
        foreach ($modules_data as $module => $data) {
            foreach ($data  as $key => $value) {
                //key is category type
                //check if category type is duplicate
                if (!in_array($key, $category_types)) {
                    $category_types[] = $key;
                } else {
                    echo __('lang_v1.duplicate_taxonomy_type_found');
                    exit;
                }

                if ($category_type == $key) {
                    $module_data = $value;
                }
            }
        }

        if (!in_array($category_type, $category_types)) {
            echo __('lang_v1.taxonomy_type_not_found');
            exit;
        }
        return $module_data;
    }
}
