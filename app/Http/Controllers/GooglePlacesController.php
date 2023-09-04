<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use \App\Models\StoreLocation;


/**
 * @OA\Info(
 *     title="Store Locator API",
 *     version="1.0.0",
 *     description="API for fetching store coordinates from Google Places",
 *     @OA\Contact(
 *         email="samson_ude@yahoo.com",
 *         name="Samson Ude"
 *     )
 * )
 * @OA\Tag(
 *     name="Store Locations",
 *     description="API endpoints for store locations"
 * )
 * @OA\Schema(
 *     schema="Coordinates",
 *     @OA\Property(property="latitude", type="number", format="float", example=40.7128),
 *     @OA\Property(property="longitude", type="number", format="float", example=-74.0060)
 * )
 */
class GooglePlacesController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/store-locations",
     *     summary="Get coordinates (latitude and longitude) from Google Places",
     *     tags={"Store Locations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="storeaddress", type="string", example="25 admiralty road, Lekki, Lagos"),
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Coordinates fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Coordinates")
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Validation errors",
     *         @OA\JsonContent(type="object", @OA\Property(property="error", type="object", @OA\Property(property="place_name", type="array", @OA\Items(type="string")))),
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Location not found",
     *         @OA\JsonContent(type="object", @OA\Property(property="error", type="string")),
     *     ),
     * )
     */
    public function storeLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'storeaddress' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $storeAddress = $request->storeaddress;

        $apiKey = env('GOOGLE_PLACES_API_KEY');

        $response = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
            'input' => $storeAddress,
            'inputtype' => 'textquery',
            'fields' => 'geometry/location',
            'key' => $apiKey,
        ]);

        $data = $response->json();

        if ($response->successful() && isset($data['candidates'][0]['geometry']['location'])) {
            $location = $data['candidates'][0];

            $storeLocation = storeLocation::create([
                "name" => $location['name'],
                "address" => $location['formatted_address'],
                "latitude" =>  $location['geometry']['location']['lat'],
                "longitude" => $location['geometry']['location']['lng']
            ]);
    
            return response()->json($storeLocation, 201);
        }
        return response()->json(['error' => 'Location not found'], 404);
    }

    /**
     * @OA\Get(
     *     path="/api/store-locations",
     *     summary="Get all store locations",
     *     tags={"Store Locations"},
     *     @OA\Response(
     *         response="200",
     *         description="List of store locations",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StoreLocation")),
     *     ),
     * )
     */
    public function getStoreLocations()
    {
        $storeLocations = StoreLocation::all();

        return response()->json($storeLocations);
    }

}
