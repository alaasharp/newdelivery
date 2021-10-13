<?php

namespace App\Services;

use App\Product;
use App\TaxGroup;
use App\Category;
use App\City;
use App\Restaurant;
use \Maatwebsite\Excel\Facades\Excel ;
  use Illuminate\Support\Collection;
 

use App\Imports\results;


/**
 * Service for bulk products import
 */
class ProductsImportService
{
    public $allRows;

	public function import($file, $city_id = null, $restaurant_id = null,$category_id=null)
    {
        $updated = 0;
        $created = 0;
        $row = 1;

       // $data =  Excel::toArray(new results, $file ,'s3', \Maatwebsite\Excel\Excel::XLSX);
       
       Excel::load($file, function($reader) {

        $this->allRows = $reader->all()->toArray(); 
    });
     // dd($this->allRows );
         if (true) {
           /*  while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                if ($row == 1) {
                    $row++;
                    continue;
                }*/
                foreach($this->allRows as $data){
                $row++;
                $data = array_values($data);
               
                $taxGroup = TaxGroup::where('name', $data[2])->first();
                $taxGroupId = null;
                if ($taxGroup != null) {
                    $taxGroupId = $taxGroup->id;
                }
               $category = Category::where('city_id', $city_id)->
                    where('city_id', $city_id)->
                    where('restaurant_id', $restaurant_id)->
                    where('name', $data[1])->
                    first();
                $categoryId = null;
                if ($category != null) {
                    $categoryId = $category->id;
                }
                else {
                    $category = Category::create([
                        'name' => $data[1],
                        'city_id' => $city_id,
                        'restaurant_id' => $restaurant_id
                    ]);
                    $categoryId = $category->id;
                } 
                $categoryId = $categoryId ;
                $product = Product::where('name', $data[0])->
                    where('category_id', $categoryId)->
                    first();
                $productData = [
                    'category_id' => $categoryId,
                    'tax_group_id' => $taxGroupId,
                    'name' => $data[0],
                    'price' => $data[3],
                    'barcode' => $data[4],
                    'erp_quantity' => $data[5],
                    'description'=>$data[6],
                    'archive'=>1  ,
                    'vendor_id'=>auth()->user()->vendor_id                
                ];
                if ($product == null) {
                    $product = new Product($productData);
                    $created++;
                }
                else {
                    $product->fill($productData);
                    $updated++;
                }
                $product->save();
            }
            
        }
        return [
            'updated' => $updated,
            'created' => $created
        ];
    }

    //-----------------
    public function importCategories($file,$city_id = null,   $restaurant_id )
    {
        $updated = 0;
        $created = 0;
        $row = 1;

       // $data =  Excel::toArray(new results, $file ,'s3', \Maatwebsite\Excel\Excel::XLSX);
       
       Excel::load($file, function($reader) {

        $this->allRows = $reader->all()->toArray(); 
    });
    // dd($this->allRows );
         if (true) {
           /*  while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                if ($row == 1) {
                    $row++;
                    continue;
                }*/
                foreach($this->allRows as $data){
                $row++;
                $data = array_values($data);
                $maincategory=null ;
                if(!empty($data[1]))
                 $maincategory = Category:: where('restaurant_id', $restaurant_id)->where('name', $data[1])->first();                
                 $category = Category::where('restaurant_id', $restaurant_id)->where('name', $data[0])->first();
                $categoryId = null;
                if ($maincategory != null) {
                    $categoryId = $maincategory->id;
                }
                else if(!empty($data[1])) {
                    $category = Category::create([
                        'name' => $data[1],                      
                        'restaurant_id' => $restaurant_id
                    ]);
                    $categoryId = $category->id;
                } 
                if(!empty($data[0])) {
                    $category = Category::create([
                        'name' => $data[0],                      
                        'restaurant_id' => $restaurant_id,
                        'parent_id' => $categoryId,
                    ]); $created++;
                    
                } 
                
                
                 
            }
            
        }
        return [
            'updated' => $updated,
            'created' => $created
        ];
    }
}