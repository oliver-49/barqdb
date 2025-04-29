<?php

// namespace App\Admin\Controllers;

// use App\Models\Zone;
// use Encore\Admin\Controllers\AdminController;
// use Encore\Admin\Form;
// use Encore\Admin\Grid;
// use Encore\Admin\Show;
// class ZoneController extends AdminController
// {
//     protected $title = 'Zones';

//     protected function grid()
//     {
//         $grid = new Grid(new Zone());

//         $grid->column('id', __('ID'))->sortable();
//         $grid->column('name', __('Name'));
//         $grid->column('status', __('Status'))->bool();

//         // Show the lat-lng text we defined in the accessor
//         $grid->column('coordinates_lat_lng_text', __('Coordinates (Lat, Lng)'))
//              ->display(function ($val) {
//                  // If null or empty, show a dash
//                  return $val ?: 'youssef';
//              });
//         $grid->column('created_at', __('created_at'));
//         $grid->column('updated_at', __('updated_at'));
//         $grid->column('restaurant_wise_topic', __('restaurant_wise_topic	restaurant_wise_topic'));
//         $grid->column('customer_wise_topic', __('customer_wise_topic'));
//         $grid->column('deliveryman_wise_topic', __('deliveryman_wise_topic'));        


//         $grid->model()->latest();
//         return $grid;
//     }

//     protected function detail($id)
//     {
//         $show = new Show(Zone::findOrFail($id));

//         $show->field('id', 'ID');
//         $show->field('name', 'Name');
//         $show->field('status', 'Status')->using([1 => 'Active', 0 => 'Inactive']);

//         // Again, use coordinates_lat_lng_text
//         $show->field('coordinates_lat_lng_text', 'Coordinates (Lat, Lng)');

//         return $show;
//     }

//     protected function form()
//     {
//         $form = new Form(new Zone());

//         // Basic fields
//         $form->text('name', 'Name')->required();
//         $form->switch('status', 'Status')->default(1);

//         // Coordinates text area and the map script (same as before)
//         $form->textarea('coordinates', 'Coordinates')->readonly();
//         $form->html('
//             <div id="map" style="width: 100%; height: 400px;"></div>
//             <script>
//                 function initMap() {
//                     var map = new google.maps.Map(document.getElementById("map"), {
//                         center: { lat: 30.0444, lng: 31.2357 },
//                         zoom: 10
//                     });

//                     var drawingManager = new google.maps.drawing.DrawingManager({
//                         drawingMode: google.maps.drawing.OverlayType.POLYGON,
//                         drawingControl: true,
//                         drawingControlOptions: {
//                             position: google.maps.ControlPosition.TOP_CENTER,
//                             drawingModes: ["polygon"]
//                         },
//                         polygonOptions: {
//                             editable: true,
//                             draggable: true
//                         }
//                     });

//                     drawingManager.setMap(map);

//                     google.maps.event.addListener(drawingManager, "overlaycomplete", function(event) {
//                         if (event.type === google.maps.drawing.OverlayType.POLYGON) {
//                             var path = event.overlay.getPath();
//                             var coordinates = "";
//                             for (var i = 0; i < path.getLength(); i++) {
//                                 var lat = path.getAt(i).lat();
//                                 var lng = path.getAt(i).lng();
//                                 coordinates += "(" + lat + ", " + lng + "), ";
//                             }
//                             coordinates = coordinates.slice(0, -2);
//                             document.querySelector("textarea[name=coordinates]").value = coordinates;
//                         }
//                     });
//                 }
//                 var script = document.createElement("script");
//                 script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU&libraries=drawing&callback=initMap";
//                 document.head.appendChild(script);
//             </script>
//         ', 'Draw a polygon on the map');

//         // saving() callback that does ST_GeomFromText...
//         $form->saving(function (Form $form) {
//             if ($form->coordinates) {
//                 // same logic as before: parse "(lat, lng), (lat, lng), ..." => (lng lat)
//                 // build WKT => ST_GeomFromText...
//             }
//         });

//         return $form;
//     }
// }




//      2
namespace App\Admin\Controllers;

use App\Models\Zone;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class ZoneController extends AdminController
{
    // Shown as the resource title in Laravel-Admin
    protected $title = 'Zones';

    /**
     * GRID view: the table of all Zones
     */
    protected function grid()
    {
        $grid = new Grid(new Zone());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('status', __('Status'))->bool();

        // Instead of showing raw 'coordinates' (binary),
        // show the WKT string from getCoordinatesTextAttribute().
        $grid->column('coordinates_text', __('Coordinates'))
             ->display(function ($val) {
                 return $val ?: '—'; // if null, show dash
             });

        $grid->model()->latest(); // optional: newest first
        return $grid;
    }

    /**
     * DETAIL view: when you click "view" on a single zone
     */
    protected function detail($id)
    {
        $show = new Show(Zone::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('status', __('Status'))->using([1 => 'Active', 0 => 'Inactive']);

        // Show the WKT text from our model accessor
        $show->field('coordinates_text', __('Coordinates'));
        $show->field('created_at', __('created_at'));

        return $show;
    }

    /**
     * FORM view: create/edit a zone
     */
    protected function form()
    {
        $form = new Form(new Zone());

        // Basic fields
        $form->text('name', __('Name'))->required();
        $form->switch('status', __('Status'))->default(1);

        // Coordinates text area (readonly) – will be filled by the map drawing code
        $form->textarea('coordinates', __('Coordinates'))
             ->readonly()
             ->help('Draw a polygon on the map to auto-generate the coordinates.');

        // Embed Google Map + Drawing Manager
        // Replace "YOUR_GOOGLE_API_KEY" with a real Maps JavaScript API key
        $form->html('
            <div id="map" style="width: 100%; height: 400px;"></div>
            <script>
                function initMap() {
                    var map = new google.maps.Map(document.getElementById("map"), {
                        center: { lat: 30.0444, lng: 31.2357 }, // Cairo sample
                        zoom: 10
                    });

                    var drawingManager = new google.maps.drawing.DrawingManager({
                        drawingMode: google.maps.drawing.OverlayType.POLYGON,
                        drawingControl: true,
                        drawingControlOptions: {
                            position: google.maps.ControlPosition.TOP_CENTER,
                            drawingModes: ["polygon"]
                        },
                        polygonOptions: {
                            editable: true,
                            draggable: true
                        }
                    });

                    drawingManager.setMap(map);

                    google.maps.event.addListener(drawingManager, "overlaycomplete", function(event) {
                        if (event.type === google.maps.drawing.OverlayType.POLYGON) {
                            // Get the polygon path
                            var path = event.overlay.getPath();
                            var coords = "";

                            // Build a string like "(lat, lng), (lat, lng), ..."
                            for (var i = 0; i < path.getLength(); i++) {
                                var lat = path.getAt(i).lat();
                                var lng = path.getAt(i).lng();
                                coords += "(" + lat + ", " + lng + "), ";
                            }
                            // Remove the trailing comma and space
                            coords = coords.slice(0, -2);

                            // Place in the textarea
                            document.querySelector("textarea[name=coordinates]").value = coords;
                        }
                    });
                }

                // Load Google Maps script
                var script = document.createElement("script");
                script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU&libraries=drawing&callback=initMap";
                document.head.appendChild(script);
            </script>
        ', 'Draw Polygon');

        // Convert the (lat, lng) pairs to a WKT polygon
        $form->saving(function (Form $form) {
            if ($form->coordinates) {
                // e.g. "(30.0444, 31.2357), (30.0450, 31.2360), ..."
                preg_match_all('/\(([^)]+)\)/', $form->coordinates, $matches);

                if (empty($matches[1])) {
                    throw new \Exception("Invalid coordinate format: No valid (lat, lng) pairs found.");
                }

                $polygonCoords = [];

                foreach ($matches[1] as $coord) {
                    $points = explode(',', $coord);
                    if (count($points) !== 2) {
                        throw new \Exception("Invalid coordinate: '$coord'");
                    }

                    $lat = trim($points[0]);
                    $lng = trim($points[1]);

                    if (!is_numeric($lat) || !is_numeric($lng)) {
                        throw new \Exception("Coordinates must be numeric. Found: ($lat, $lng)");
                    }

                    // MySQL wants (X Y) => (longitude latitude)
                    $polygonCoords[] = "{$lng} {$lat}";
                }

                // Close the polygon (repeat the first point at the end if needed)
                if ($polygonCoords[0] !== end($polygonCoords)) {
                    $polygonCoords[] = $polygonCoords[0];
                }

                // Build WKT => "POLYGON((lng lat, lng lat, ...))"
                $wktPolygon = "POLYGON((" . implode(", ", $polygonCoords) . "))";

                // ST_GeomFromText('...', 4326) => no axis-order param
                $form->coordinates = DB::raw("ST_GeomFromText('{$wktPolygon}', 4326)");
            }
        });

        return $form;
    }
}








//    1
// namespace App\Admin\Controllers;

// use App\Models\Zone;
// use Encore\Admin\Controllers\AdminController;
// use Encore\Admin\Form;
// use Encore\Admin\Grid;
// use Encore\Admin\Show;

// class ZoneController extends AdminController
// {
//     // Title displayed in Laravel-Admin
//     protected $title = 'Zones';

//     /**
//      * GRID view: shows a table of zones
//      */
//     protected function grid()
//     {
//         $grid = new Grid(new Zone());

//         $grid->column('id', 'ID')->sortable();
//         $grid->column('name', 'Name')->sortable();
//         $grid->column('status', 'Status')->bool();

//         // Display a snippet of the geometry data
//         $grid->column('coordinates', 'Coordinates')->display(function ($val) {
//             // If your DB returns raw geometry, this might be unreadable binary.
//             // If you want, you can store a textual WKT for display, or just show a snippet:
//             return is_string($val) ? mb_strimwidth($val, 0, 50, '...') : $val;
//         });

//         // Show newest first (optional)
//         $grid->model()->latest();

//         return $grid;
//     }

//     /**
//      * DETAIL view: when you click "view" on a zone
//      */
//     protected function detail($id)
//     {
//         $show = new Show(Zone::findOrFail($id));

//         $show->field('id', 'ID');
//         $show->field('name', 'Name');
//         $show->field('status', 'Status')->using([1 => 'Active', 0 => 'Inactive']);
//         $show->field('coordinates', 'Coordinates');

//         return $show;
//     }

//     /**
//      * FORM view: create/edit a zone
//      */
//     protected function form()
//     {
//         $form = new Form(new Zone());

//         // Basic fields
//         $form->text('name', 'Name')->required();
//         $form->switch('status', 'Status')->default(1);

//         // Coordinates field for storing polygon data
//         $form->textarea('coordinates', 'Coordinates')
//              ->readonly()
//              ->help('Coordinates will be generated automatically when you draw on the map.');

//         /**
//          * Embed Google Maps with the drawing library.
//          * - The "DrawingManager" allows you to draw polygons.
//          * - Once drawn, we take each vertex (lat,lng) and fill the 
//          *   <textarea name="coordinates"> with something like:
//          *      (30.0444, 31.2357), (30.0455, 31.2388), ...
//          */
//         $form->html('
//             <div id="map" style="width: 100%; height: 400px; margin-bottom: 20px;"></div>
//             <script>
//                 function initMap() {
//                     var map = new google.maps.Map(document.getElementById("map"), {
//                         center: { lat: 30.0444, lng: 31.2357 }, // Cairo area
//                         zoom: 10
//                     });

//                     var drawingManager = new google.maps.drawing.DrawingManager({
//                         drawingMode: google.maps.drawing.OverlayType.POLYGON,
//                         drawingControl: true,
//                         drawingControlOptions: {
//                             position: google.maps.ControlPosition.TOP_CENTER,
//                             drawingModes: ["polygon"]
//                         },
//                         polygonOptions: {
//                             editable: true,
//                             draggable: true
//                         }
//                     });

//                     drawingManager.setMap(map);

//                     google.maps.event.addListener(drawingManager, "overlaycomplete", function(event) {
//                         if (event.type === google.maps.drawing.OverlayType.POLYGON) {
//                             var path = event.overlay.getPath();
//                             var coordinates = "";

//                             // Build a string like "(lat, lng), (lat, lng), ..."
//                             for (var i = 0; i < path.getLength(); i++) {
//                                 var lat = path.getAt(i).lat();
//                                 var lng = path.getAt(i).lng();
//                                 coordinates += "(" + lat + ", " + lng + "), ";
//                             }
//                             // Remove trailing comma
//                             coordinates = coordinates.slice(0, -2);

//                             // Put the result into the textarea
//                             document.querySelector("textarea[name=coordinates]").value = coordinates;
//                         }
//                     });
//                 }

//                 // Load Google Maps script with your API key
//                 // e.g. replace "YOUR_API_KEY" with an actual key
//                 var script = document.createElement("script");
//                 script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU&libraries=drawing&callback=initMap";
//                 document.head.appendChild(script);
//             </script>
//         ', 'Draw a polygon');

//         /**
//          * The critical step: convert the text in "coordinates" into a WKT polygon:
//          *   POLYGON((lng lat, lng lat, ...))
//          * Then store with ST_GeomFromText('...', 4326).
//          */
//         $form->saving(function (Form $form) {
//             if ($form->coordinates) {
//                 // 1) Pull out each (lat, lng) pair => e.g. (30.0444, 31.2357)
//                 preg_match_all('/\(([^)]+)\)/', $form->coordinates, $matches);

//                 if (empty($matches[1])) {
//                     throw new \Exception("Invalid coordinate format: No valid coordinates found.");
//                 }

//                 $polygonCoords = [];
                
//                 // 2) Convert (lat, lng) -> "lng lat" for MySQL geometry
//                 foreach ($matches[1] as $coord) {
//                     // e.g. "30.0444, 31.2357"
//                     $parts = explode(',', $coord);
//                     if (count($parts) !== 2) {
//                         throw new \Exception("Invalid coordinate format: '$coord'");
//                     }
                    
//                     $latitude = trim($parts[0]);
//                     $longitude = trim($parts[1]);

//                     // Ensure numeric
//                     if (!is_numeric($latitude) || !is_numeric($longitude)) {
//                         throw new \Exception("Coordinates must be numeric: lat=$latitude, lng=$longitude");
//                     }

//                     // MySQL wants (X Y) => (longitude latitude)
//                     $polygonCoords[] = "{$longitude} {$latitude}";
//                 }

//                 // 3) Close the polygon by repeating the first point as the last
//                 if ($polygonCoords[0] !== end($polygonCoords)) {
//                     $polygonCoords[] = $polygonCoords[0];
//                 }

//                 // 4) Build the WKT string => "POLYGON((lng lat, lng lat, ...))"
//                 $wktPolygon = "POLYGON((" . implode(", ", $polygonCoords) . "))";

//                 // 5) Use two-argument ST_GeomFromText => no extra 'axis-order'
//                 $form->coordinates = \DB::raw("ST_GeomFromText('{$wktPolygon}', 4326)");
//             }
//         });

//         return $form;
//     }
// }


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// namespace App\Admin\Controllers;

// use App\Models\Zone;
// use Encore\Admin\Controllers\AdminController;
// use Encore\Admin\Form;
// use Encore\Admin\Grid;
// use Encore\Admin\Show;

// class ZoneController extends AdminController
// {
//     // Title displayed in Laravel-Admin
//     protected $title = 'Zones';

//     /**
//      * GRID view: shows a table of zones
//      */
//     protected function grid()
//     {
//         $grid = new Grid(new Zone());

//         $grid->column('id', 'ID')->sortable();
//         $grid->column('name', 'Name')->sortable();
//         $grid->column('status', 'Status')->bool();

//         // Just display a snippet of the raw coordinates
//         $grid->column('coordinates', 'Coordinates')->display(function ($val) {
//             // The column will store geometry data, 
//             // so if your model's getCoordinatesAttribute is returning a string, 
//             // it might look like 'AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU...' (raw geometry binary).
//             // Or if you have a custom accessor returning lat/lng pairs, you can display that.
//             return is_string($val) ? mb_strimwidth($val, 0, 50, '...') : $val;
//         });

//         $grid->model()->latest(); // optional: show newest first

//         return $grid;
//     }

//     /**
//      * DETAIL view: when you click "view" on a zone
//      */
//     protected function detail($id)
//     {
//         $show = new Show(Zone::findOrFail($id));

//         $show->field('id', 'ID');
//         $show->field('name', 'Name');
//         $show->field('status', 'Status')->using([1 => 'Active', 0 => 'Inactive']);
//         $show->field('coordinates', 'Coordinates');

//         return $show;
//     }

//     /**
//      * FORM view: create/edit a zone
//      */
//     protected function form()
//     {
//         $form = new Form(new Zone());

//         // Basic fields
//         $form->text('name', 'Name')->required();
//         $form->switch('status', 'Status')->default(1);

//         // Coordinates field for storing polygon data
//         $form->textarea('coordinates', 'Coordinates')
//              ->readonly()
//              ->help('Coordinates will be generated automatically when you draw on the map.');

//         /**
//          * Here we embed Google Maps with the drawing library.
//          * - The "DrawingManager" allows you to draw polygons.
//          * - Once drawn, we take each vertex (lat,lng) and fill the 
//          *   <textarea name="coordinates"> with something like:
//          *
//          *      (30.0444, 31.2357), (30.0455, 31.2388), ...
//          */
//         $form->html('
//             <div id="map" style="width: 100%; height: 400px; margin-bottom: 20px;"></div>
//             <script>
//                 function initMap() {
//                     var map = new google.maps.Map(document.getElementById("map"), {
//                         center: { lat: 30.0444, lng: 31.2357 }, // Cairo coords
//                         zoom: 10
//                     });

//                     var drawingManager = new google.maps.drawing.DrawingManager({
//                         drawingMode: google.maps.drawing.OverlayType.POLYGON,
//                         drawingControl: true,
//                         drawingControlOptions: {
//                             position: google.maps.ControlPosition.TOP_CENTER,
//                             drawingModes: ["polygon"]
//                         },
//                         polygonOptions: {
//                             editable: true,
//                             draggable: true
//                         }
//                     });

//                     drawingManager.setMap(map);

//                     google.maps.event.addListener(drawingManager, "overlaycomplete", function(event) {
//                         if (event.type === google.maps.drawing.OverlayType.POLYGON) {
//                             var path = event.overlay.getPath();
//                             var coordinates = "";

//                             // Build a string like "(lat, lng), (lat, lng), ..."
//                             for (var i = 0; i < path.getLength(); i++) {
//                                 var lat = path.getAt(i).lat();
//                                 var lng = path.getAt(i).lng();
//                                 coordinates += "(" + lat + ", " + lng + "), ";
//                             }

//                             // Remove trailing comma
//                             coordinates = coordinates.slice(0, -2);

//                             // Put the result into the textarea
//                             document.querySelector("textarea[name=coordinates]").value = coordinates;
//                         }
//                     });
//                 }

//                 // Load Google Maps script with your API key
//                 // MAKE SURE TO REPLACE AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU with your real key
//                 var script = document.createElement("script");
//                 script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyA9Y4ciPuB9UocKTbrQXdesBlh14Ut1YpU&libraries=drawing&callback=initMap";
//                 document.head.appendChild(script);
//             </script>
//         ', 'Draw a polygon');

//         /**
//          * The critical step: convert the text in "coordinates" into a WKT polygon:
//          *   POLYGON((lng lat, lng lat, ...))
//          * Then store with ST_GeomFromText('...', 4326).
//          */
//         $form->saving(function (Form $form) {
//             if ($form->coordinates) {
//                 // 1) Pull out each (lat, lng) pair
//                 preg_match_all('/\(([^)]+)\)/', $form->coordinates, $matches);

//                 if (empty($matches[1])) {
//                     throw new \Exception("Invalid coordinate format: No valid coordinates found.");
//                 }

//                 $polygonCoords = [];
                
//                 // 2) Convert (lat, lng) → "lng lat" (MySQL expects X Y, which is LONG LAT)
//                 foreach ($matches[1] as $coord) {
//                     // Something like "30.0444, 31.2357"
//                     $points = explode(',', $coord);
//                     if (count($points) !== 2) {
//                         throw new \Exception("Invalid coordinate format: '$coord'");
//                     }
                    
//                     $latitude = trim($points[0]);
//                     $longitude = trim($points[1]);

//                     // Ensure numeric
//                     if (!is_numeric($latitude) || !is_numeric($longitude)) {
//                         throw new \Exception("Coordinates must be numeric. Found: lat=$latitude, lng=$longitude");
//                     }

//                     // Swap to "longitude latitude"
//                     $polygonCoords[] = "{$longitude} {$latitude}";
//                 }

//                 // 3) Close the polygon by repeating the first point as the last
//                 //    This is required by WKT for polygons
//                 if ($polygonCoords[0] !== end($polygonCoords)) {
//                     $polygonCoords[] = $polygonCoords[0];
//                 }

//                 // 4) Build the WKT string
//                 //    e.g. "POLYGON((31.2357 30.0444, 31.2388 30.0455, ...))"
//                 $wktPolygon = "POLYGON((" . implode(", ", $polygonCoords) . "))";

//                 // 5) Convert to a geometry object with SRID=4326
//                 $form->coordinates = \DB::raw("ST_GeomFromText('{$wktPolygon}', 4326)");
//             }
//         });

//         return $form;
//     }
// }