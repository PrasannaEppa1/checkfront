<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Members;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class MembersController extends Controller
{
    /**
     * Finding activated members
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivatedMembers(Request $request)
    {
        // Getting all request data.
        $data = $request->all();

        // Setting default values for below parameters.
        $page = 1;
        $limit = 50;

        // Check if all parameters are passed and valid.
        $validator = Validator::make($data, [
            'no_of_days' => 'required|integer',
            'page'       => 'integer',
            'limit'      => 'integer'
        ]);

        // Throwing error if above validation fails
        if($validator->fails()){
            return response(['error' => $validator->errors(), 'Validation Error'],400);
        }
        
        $no_of_days = $data['no_of_days'];
        if(isset($data['page']) && $data['page']) {
            $page = $data['page'];
        }
        if(isset($data['limit']) && $data['limit']) {
            $limit = $data['limit'];
        }

        // DB query to get activated members within given no_of_days
        $members_query = DB::table('members as m')
                        ->select('m.user_id','m.signup_date','m.channel')
                        ->join('activity as a',function($join) {
                            $join->on('a.user_id', '=', 'm.user_id');
                            $join->on('a.act_type', '=', DB::raw("'Add_Flavour'"));
                        })
                        ->join('activity as a1',function($join) {
                            $join->on('a1.user_id', '=', 'm.user_id');
                            $join->on('a1.act_type', '=', DB::raw("'Select_Category'"));
                        })
                        ->where(DB::raw('DATEDIFF(a.act_timestamp,m.signup_date)'),'<=',$no_of_days )
                        ->where(DB::raw('DATEDIFF(a1.act_timestamp,m.signup_date)'),'<=',$no_of_days );
        // Get total count of activated members
        $members_count = $members_query->count();
        if($members_count) {
            // Get total number of pages
            $total_pages = ceil($members_count/$limit);
            $offset = ($page-1) * $limit;

            if($page > $total_pages) {
                return response(['error' => 'Page number exceeded'], 400);
            }
        
            // DB query to get all activated members within above no_of_days
            $members =  $members_query->limit($limit)
                        ->offset($offset)
                        ->get()
                        ->toArray();
            return response([ 'members' => $members, 
                                'total_pages' => $total_pages, 
                            'message' => 'Members Retrieved successfully'], 200);
        } else {
            return response([ 'members' => [], 
                            'message' => 'No Members found'], 200);

        }
        
    }

}
