<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\City;
use App\Models\Dist;
use App\Models\Road;

class DataController extends Controller
{
    public function show() {
        dd(City::get()->count(), City::first());
    }

    public function import() {
        $files = Storage::allFiles('address');

        $cityContent = Storage::get('address/0/0.json');
        $cityData = json_decode($cityContent, true);

        foreach($cityData as $obj) {
            $cityId = City::insertGetId(['name' => $obj['city']]);
            
            foreach($obj['data'] as $dist) {
                $distId = Dist::insertGetId(['city_id' => $cityId, 'name' => $dist['area']]);

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
