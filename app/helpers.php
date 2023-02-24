<?php

use Kreait\Firebase\Database;
if(!function_exists("total_users")){

    function total_users(){
        return DB::table('app_users')->get()->count();
    
    }
}
// if(!function_exists("get_reported_reasons")){

//   function get_reported_reasons($id){
//     $db = new Database; 
//     $data =  $db->getReference('/reported_posts')->getChild($id)->getSnapshot()->getValue();
//     dd($data); 
//   }
// }

if(!function_exists('getaddress'))
{
  function getaddress($lat,$lng)
  {
   // 'https://maps.googleapis.com/maps/api/geocode/json?address=Winnetka&bounds=34.172684,
  //  -118.604794|34.236144,-118.500938&key=YOUR_API_KEY';
     $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&key=AIzaSyASyo506UpWwPDGpY1HEbWQEFWDjTDA2Ls';
     $json = @file_get_contents($url);
     $data=json_decode($json);
     //dd($data);
     $status = $data->status;
     if($status=="OK")
     {
       return $data->results[0]->formatted_address;
     }
     else
     {
       return 'Not Found';
     }
  }

}