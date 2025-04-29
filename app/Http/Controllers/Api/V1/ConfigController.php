<?php

namespace App\Http\Controllers\Api\V1;
use App\Models\Zone;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Grimzy\LaravelMysqlSpatial\Types\Point;


class ConfigController extends Controller
{
        public function geocode_api(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);

        if ($validator->errors()->count()>0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
       
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$request->lat.','.$request->lng.'&key='."AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU");
        return $response->json();
    }


//   *************    0     ********************
//     // get zone by deepseek
//     public function get_zone(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'lat' => 'required',
//         'lng' => 'required',
//     ]);

//     if ($validator->errors()->count() > 0) {
//         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
//     }

//     // Create a Point object
//     $point = new Point($request->lat, $request->lng);

//     // Find the zone that contains the point
//     $zone = Zone::contains('coordinates', $point)->latest()->get();

//     if ($zone) {
//         return response()->json(['zone_id' => $zone->id], 200);
//     } else {
//         return response()->json(['zone_id' => null], 200);
//     }
// } 
//      ***********************************************



//get_zone by video
    public function get_zone(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->errors()->count()>0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $point = new Point($request->lat, $request->lng);
        $zones = Zone::contains('coordinates', $point)->latest()->get();
        /** if(count($zones)<1)
         * {
         * return response()->json(['message'=>trans('messages.service_not_available_in_this_area_now')], 404);
         * }
         * foreach($zones as $zone)
         * {
         * if($zone->status)
         * {
         * return response()->json(['zone_id'=>$zone->id], 200);
         * }
         * }
         * return response()->json(['message'=>trans('messages.we_are_temporarily_unavailable_in_this_area')], 403);
         * 
         */
        return response()->json(['zone_id'=>1], 200);
    }




// configuration
    public function configuration(){
        return response()->json([
            'business_name' => BusinessSetting::where(['key' => 'business_name'])->first()->value,
            'base_urls' =>[
                'customer_image_url' => asset('storage/profile'),
                'business_logo_url' => asset('storage/business'),
            ],
            'country' => BusinessSetting::where(['key' => 'country'])->first()->value,
            'default_locatioon' => [ 'lat'=>'23.757989', 'lng'=>'90.360587'],
        ]);
            
    }

// place_api_autocomplete
    public function place_api_autocomplete(Request $request){
        $validator = Validator::make($request->all(), [
            'search_text' => 'required',
        ]);

        if ($validator->errors()->count()>0) {
            return response()->json(
                ['errors' => Helpers::error_processor($validator)]
                , 403);
        }
        $response = Http::get(
            'https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.$request['search_text'].'&key='."AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU");
            return $response->json();
    }


    // place_api_details
    public function place_api_details(Request $request){
        $validator = Validator::make($request->all(), [
            'placeid' => 'required',
        ]);

        if ($validator->errors()->count()>0) {
            return response()->json(
                ['errors' => Helpers::error_processor($validator)]
                , 403);
        }
        $response = Http::get(
            'https://maps.googleapis.com/maps/api/place/details/json?placeid='.$request['placeid'].'&key='."AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU");
            return $response->json();
    }




// last one which is working
    //     public function get_zone(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'lat' => 'required',
    //         'lng' => 'required',
    //     ]);

    //     if ($validator->errors()->count()>0) {
    //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //     }

    //     // old one
    //     $point = new Point($request->lat,$request->lng);
    //     $zones = Zone::contains('coordinates', $point)->latest()->get();
      
    //         //   // new one by chat GPT
    //         //   $longitude = $request->lng;
    //         //   $latitude = $request->lat;
      
    //         //   $zones = Zone::whereRaw("ST_Contains(coordinates, ST_GeomFromText(?, 4326))", ["POINT($latitude $longitude)"])
    //         //      ->latest()
    //         //      ->get();
      
      
    //     /* if(count($zones)<1)
    //     {
    //         return response()->json(['message'=>trans('messages.service_not_available_in_this_area_now')], 404);
    //     }
    //     foreach($zones as $zone)
    //     {
    //         if($zone->status)
    //         {
    //             return response()->json(['zone_id'=>$zone->id], 200);
    //         }
    //     }*/
    //     //return response()->json(['message'=>trans('messages.we_are_temporarily_unavailable_in_this_area')], 403);
    //      return response()->json(['zone_id'=>1], 200);
    // }






        // from website
    // public function get_zone(Request $request)
    // {
    //       $validator = Validator::make($request->all(), [
    //         'lat' => 'required',
    //         'lng' => 'required',
    //     ]);

    //     if ($validator->errors()->count()>0) {
    //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //     }
    //     $point = new Point($request->lat,$request->lng);
        

    //     $zones = Zone::contains('coordinates', $point)->first();
    //     if(empty($zones)){
    //         return response()->json(['code'=>-1,'message'=>'error']);
    //     }
    //     return response()->json(['code'=>0,'message'=>'success','data'=>$zones->id]);
      
     
    // }



}
