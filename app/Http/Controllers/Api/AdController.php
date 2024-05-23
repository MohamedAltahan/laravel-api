<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\Ad;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdRequest;
use App\Http\Resources\AdResource;

class AdController extends Controller
{
    public function index()
    {
        $ads = Ad::latest()->paginate(5);
        if (count($ads) > 0) {
            if ($ads->total() > $ads->perPage()) {
                $data = [
                    'records' => AdResource::collection($ads),
                    'pagination links' => [
                        'current page' => $ads->currentPage(),
                        'per page' => $ads->perPage(),
                        'total' => $ads->total(),
                        'links' => [
                            'first' => $ads->url(1),
                            'last' => $ads->url($ads->lastPage()),
                        ],
                    ],
                ];
            } else {
                $data = AdResource::collection($ads);
            }
            return ApiResponse::sendResponse(200, 'Ads Retrieved Successfully', $data);
        }
        return ApiResponse::sendResponse(200, 'No Ads available', []);
    }

    //latest ads-------------------------------------------------------------------------
    public function latest()
    { //2 is a variable number
        $ads = Ad::latest()->take(2)->get();
        if (count($ads) > 0) {
            return ApiResponse::sendResponse(200, 'Latest Ads Retrieved Successfully', AdResource::collection($ads));
        }
        return ApiResponse::sendResponse(200, 'There are no latest ads', []);
    }

    //category(group)-------------------------------------------------------------------
    public function domain($domain_id)
    {
        $ads = Ad::where('domain_id', $domain_id)->latest()->get();
        if (count($ads) > 0) {
            return ApiResponse::sendResponse(200, 'Ads in the domain retrieved successfully', AdResource::collection($ads));
        }
        return ApiResponse::sendResponse(200, 'empty', []);
    }

    //search---------------------------------------------------------------------------
    public function search(Request $request)
    {
        $word = $request->has('search') ? $request->input('search') : null;
        //if the condition is false , where conditon will be omited and we will get all the latest ads
        $ads = Ad::when($word != null, function ($q) use ($word) {
            $q->where('title', 'like', '%' . $word . '%');
        })->latest()->get();

        if (count($ads) > 0) {
            return ApiResponse::sendResponse(200, 'Search completed', AdResource::collection($ads));
        }
        return ApiResponse::sendResponse(200, 'No matching data', []);
    }
    // creat ad-----------------------------------------------------------------------
    public function create(AdRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $record = Ad::create($data);
        if ($record) return ApiResponse::sendResponse(201, 'Your Ad created successfully', new AdResource($record));
    }
    // update add --------------------------------------------------------------
    public function update(AdRequest $request, $adId)
    {
        $ad = Ad::findOrFail($adId);
        if ($ad->user_id != $request->user()->id) {
            return ApiResponse::sendResponse(403, 'You aren\'t allowed to take this action', []);
        }

        $data = $request->validated();
        $updating = $ad->update($data);
        if ($updating) {
            return ApiResponse::sendResponse(201, 'Your Ad updated successfully', new AdResource($ad));
        }
    }

    //delete ad--------------------------------------------------------------------------------
    public function delete(Request $request, $adId)
    {
        $ad = Ad::findOrFail($adId);
        if ($ad->user_id != $request->user()->id) {
            return ApiResponse::sendResponse(403, 'You aren\'t allowed to take this action', []);
        }
        $success = $ad->delete();
        if ($success) {
            return ApiResponse::sendResponse(200, 'Your Ad deleted successfully', []);
        }
    }

    //get all my advertisements
    public function myads(Request $request)
    {
        $ads = Ad::where('user_id', $request->user()->id)->latest()->get();
        if (count($ads) > 0) {
            return ApiResponse::sendResponse(200, 'My ads retrieved successfully', AdResource::collection($ads));
        }
        return ApiResponse::sendResponse(200, 'You don\'t have any ads', []);
    }
}
