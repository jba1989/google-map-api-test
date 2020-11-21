<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\City;
use App\Models\Dist;
use App\Models\Road;

class DataController extends Controller
{
    private function getQueryAutoComplete($address) {
        $url = 'https://maps.googleapis.com/maps/api/place/queryautocomplete/json';

        $result = Http::get($url, [
            'key' => env('GOOGLE_API_KEY'),
            'input' => $address,
        ]);

        $resultArr = $result->json();

        if (empty($resultArr['status']) || $resultArr['status'] != 'OK') {
            return [];
        }

        return $resultArr['predictions'][0];
    }
    
    private function getPlaceDetails($placeId) {
        $data = ['lat' => null, 'lng' => null];

        $url = 'https://maps.googleapis.com/maps/api/place/details/json';

        $result = Http::get($url, [
            'key' => env('GOOGLE_API_KEY'),
            'place_id' => $placeId,
        ]);

        $resultArr = $result->json();

        if (!empty($resultArr) && $resultArr['status'] == 'OK') {
            $data = $resultArr['result']['geometry']['location'];
        }

        return $data;
    }

    public function show() {
        dd(City::get()->count(), Dist::count(), Road::count());
    }

    public function getAddress(Request $request) {    
        $data = [
            'zip' => null,
            'city' => null,
            'area' => null,
            'road' => null,
            'lane' => null,
            'alley' => null,
            'no' => null,
            'floor' => null,
            'address' => null,
            'filename' => null,
            'latitude' => null,
            'lontitue' => null,
            'full_address' => null,
        ];

        $prediction = $this->getQueryAutoComplete($request->get('address'));

        if (empty($prediction)) return response()->json($data);

        $placeId = $prediction['place_id'];
        $location = $this->getPlaceDetails($placeId);

        $city = array_reverse($prediction['terms'])[1]['value'];
        $dist = array_reverse($prediction['terms'])[2]['value'];
        
        $search = DB::table('cities as c')
            ->join('dists as d', 'd.city_id', '=', 'c.id')
            ->where('c.name', $city)
            ->where('d.name', $dist)
            ->select('c.name as city', 'd.name as dist', 'd.zip', 'd.filename')
            ->first();

        $reg = '/([\S]+路)?([\S]+段)?([\S]+街)?([\S]+巷)?([\S]+弄)?([\S-]+號)?([\S]+樓)?/';
        preg_match($reg, $prediction['structured_formatting']['main_text'], $matches);

        $data['zip'] = !empty($search) ? $search->zip : null;
        $data['city'] = $city;
        $data['area'] = $dist;
        $data['road'] = ($matches[1] ?? null) . ($matches[2] ?? null) . ($matches[3] ?? null);
        $data['lane'] = !empty($matches[4]) ? intval($matches[4]) : null;
        $data['alley'] = !empty($matches[5]) ? intval($matches[5]) : null;
        $data['no'] = !empty($matches[6]) ? intval(str_replace('之', '-', $matches[6])) : null;
        $data['floor'] = !empty($matches[7]) ? intval($matches[7]) : null;
        $data['address'] = null;
        $data['filename'] = !empty($search) ? $search->filename : null;
        $data['latitude'] = $location['lat'];
        $data['lontitue'] = $location['lng'];
        $data['full_address'] = $prediction['description'];

        return response()->json($data);
    }

    public function import() {
        $files = Storage::allFiles('address');

        $cityContent = Storage::get('address/0/0.json');
        $cityData = json_decode($cityContent, true);

        foreach($cityData as $obj) {
            $cityId = City::insertGetId(['name' => $obj['city']]);
            
            foreach($obj['data'] as $dist) {
                $distId = Dist::insertGetId(['city_id' => $cityId, 'name' => $dist['area'], 'zip' => $dist['zip'], 'filename' => $dist['filename']]);

                $path = 'address/' . substr($dist['filename'], 0, 1) . '/' . $dist['filename'] . '.json';

                if (!in_array($path, $files)) continue;

                $roadContent = Storage::get('address/' . substr($dist['filename'], 0, 1) . '/' . $dist['filename'] . '.json');
                $roadData = json_decode($roadContent, true);

                foreach($roadData as $road) {
                    Road::insert([
                        'dist_id' => $distId,
                        'name' => $road['name'],
                        'abc' => $road['abc'],
                    ]);
                }
            }
        }

        dd('finished');
    }
}
