<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\DB;
// use Encore\Admin\Traits\DefaultDatetimeFormat;

// class Zone extends Model
// {
//     use HasFactory,DefaultDatetimeFormat;

//     protected $table = 'zones';

//     // Example fillable
//     protected $fillable = ['name', 'status', 'coordinates'];

//     /**
//      * Get the raw WKT polygon (ST_AsText) then convert (lng lat) => (lat, lng).
//      */
//     public function getCoordinatesLatLngTextAttribute()
//     {
//         // 1) Fetch the WKT polygon, e.g. "POLYGON((lng1 lat1, lng2 lat2, ...))"
//         $wkt = DB::table($this->table)
//             ->selectRaw("ST_AsText(coordinates) as wkt_polygon")
//             ->where('id', $this->id)
//             ->value('wkt_polygon');

//         if (!$wkt) {
//             return null;
//         }

//         // 2) Extract the numeric pairs between "POLYGON((" and "))"
//         //    Example WKT: "POLYGON((31.05 30.39, 31.06 30.40, ...))"
//         //    We'll capture "31.05 30.39, 31.06 30.40, ...".
//         if (!preg_match('/POLYGON\\(\\((.+)\\)\\)/', $wkt, $matches)) {
//             return $wkt; // Not a standard polygon? Just return the raw WKT
//         }

//         // 3) Split on commas to get each "lng lat" pair
//         $pairs = explode(',', $matches[1]); // e.g. ["31.05 30.39", "31.06 30.40", ...]

//         $result = [];
//         foreach ($pairs as $pair) {
//             $pair = trim($pair);
//             // Each pair is "lng lat"
//             $coords = preg_split('/\s+/', $pair); // split by space
//             if (count($coords) !== 2) {
//                 // If something’s off, skip or handle gracefully
//                 continue;
//             }
//             list($lng, $lat) = $coords; // MySQL WKT = "lng lat"

//             // 4) Now swap them to (lat, lng) for a more standard display
//             $result[] = "($lat, $lng)";
//         }

//         // 5) Join them with commas or line breaks
//         // e.g.: "(30.39, 31.05), (30.40, 31.06), ..."
//         return implode(', ', $result);
//     }
// }


// deepseek edit 2


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait; // Add this line

class Zone extends Model
{
    use HasFactory, SpatialTrait; // Add SpatialTrait here

    protected $table = 'zones';

    // Define the spatial fields
    protected $spatialFields = [
        'coordinates',
    ];

    // (Optional) If you want mass-assignable fields:
    protected $fillable = ['name', 'status', 'coordinates'];

    /**
     * Accessor to convert binary geometry to a WKT string.
     * This way, we can display something like: "POLYGON((31.05 30.39, ...))"
     * rather than gibberish characters.
     */
    public function getCoordinatesTextAttribute()
    {
        // If the zone isn't in the DB yet, or "coordinates" is null, return nothing
        if (!$this->id || !$this->coordinates) {
            return null;
        }

        // Query MySQL to get the geometry as text
        return DB::table($this->table)
            ->selectRaw("ST_AsText(coordinates) as wkt_polygon")
            ->where('id', $this->id)
            ->value('wkt_polygon');
    }
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// //  2   the coordenates displayed as -------
// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\DB;

// class Zone extends Model
// {
//     use HasFactory;

//     protected $table = 'zones';

//     // (Optional) If you want mass-assignable fields:
//     protected $fillable = ['name', 'status', 'coordinates'];

//     /**
//      * Accessor to convert binary geometry to a WKT string.
//      * This way, we can display something like: "POLYGON((31.05 30.39, ...))"
//      * rather than gibberish characters.
//      */
//     public function getCoordinatesTextAttribute()
//     {
//         // If the zone isn't in the DB yet, or "coordinates" is null, return nothing
//         if (!$this->id || !$this->coordinates) {
//             return null;
//         }

//         // Query MySQL to get the geometry as text
//         return DB::table($this->table)
//             ->selectRaw("ST_AsText(coordinates) as wkt_polygon")
//             ->where('id', $this->id)
//             ->value('wkt_polygon');
//     }
// }




//     1
// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class Zone extends Model
// {
//     use HasFactory;

//     // Make sure your DB table is zones
//     protected $table = 'zones';

//     // If you want mass assignment for these fields
//     protected $fillable = ['name', 'status', 'coordinates'];

//     public function orders()
//     {
//         return $this->hasManyThrough(Order::class);
//     }

//     public function deliverymen()
//     {
//         return $this->hasMany(DeliveryMan::class);
//     }

//     public function scopeActive($query)
//     {
//         return $query->where('status', '=', 1);
//     }
// }


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// from wedsite 

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
// use Grimzy\LaravelMysqlSpatial\Types\Point;
// use Grimzy\LaravelMysqlSpatial\Types\Polygon;
// use Grimzy\LaravelMysqlSpatial\Types\LineString;

// class Zone extends Model
// {
//     use HasFactory;
//     use SpatialTrait;

//     protected $spatialFields = [
//         'coordinates'
//     ];


//     public function orders()
//     {
//         return $this->hasManyThrough(Order::class);
//     }

//     public function deliverymen()
//     {
//         return $this->hasMany(DeliveryMan::class);
//     }

//     public function scopeActive($query)
//     {
//         return $query->where('status', '=', 1);
//     }

//     public function getCoordinatesAttribute($value)
//     {
//         if($value){

//         $data_str = "";
//         foreach($value as $coord)
//         {
//             foreach ($coord as $val){
//                 $data_str = $data_str."({$val->getlat()},{$val->getlng()}),";
//             }
//         }
//         return substr($data_str,0,-1);
//         }
//         return $value;
//     }

//     public function setCoordinatesAttribute($value)
//     {

//         $lastcord = [];
//         $polygon= [];
//         foreach(explode('),(',trim($value,'()')) as $index=>$single_array){
//             if($index == 0)
//             {
//                 $lastcord = explode(',',$single_array);
//             }
//             $coords = explode(',',$single_array);
//             $polygon[] = new Point($coords[0], $coords[1]);
//         }
//         $polygon[] = new Point($lastcord[0], $lastcord[1]);
//         $coordinates = new Polygon([new LineString($polygon)]);
//         $this->attributes['coordinates'] = $coordinates;

//     }

//     /////////////////////////////////////
//     // public function get_zone(Request $request)
//     // {
//     //       $validator = Validator::make($request->all(), [
//     //         'lat' => 'required',
//     //         'lng' => 'required',
//     //     ]);

//     //     if ($validator->errors()->count()>0) {
//     //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
//     //     }
//     //     $point = new Point($request->lat,$request->lng);
        

//     //     $zones = Zone::contains('coordinates', $point)->first();
//     //     if(empty($zones)){
//     //         return response()->json(['code'=>-1,'message'=>'error']);
//     //     }
//     //     return response()->json(['code'=>0,'message'=>'success','data'=>$zones->id]);
      
     
//     // }
// }