<?php
namespace DTApi\Helpers;

use Carbon\Carbon;
use DTApi\Models\Job;
use DTApi\Models\User;
use DTApi\Models\Language;
use DTApi\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeHelper
{
    public static function fetchLanguageFromJobId($id)
    {
        $language = Language::findOrFail($id);
        return $language1 = $language->language;
    }

    public static function getUsermeta($user_id, $key = false)
    {
        return $user = UserMeta::where('user_id', $user_id)->first()->$key;
        if (!$key)
            return $user->usermeta()->get()->all();
        else {
            $meta = $user->usermeta()->where('key', '=', $key)->get()->first();
            if ($meta)
                return $meta->value;
            else return '';
        }
    }

    public static function convertJobIdsInObjs($jobs_ids)
    {

        $jobs = array();
        foreach ($jobs_ids as $job_obj) {
            $jobs[] = Job::findOrFail($job_obj->id);
        }
        return $jobs;
    }
    // $due_time and $created_at are properly validated before passing them into this function. 
    // This will help to prevent unexpected errors and ensure that the function behaves as expected.
    public static function willExpireAt($due_time, $created_at)
    {
        // Parse the input strings into Carbon instances
        $due_time = Carbon::parse($due_time);
        $created_at = Carbon::parse($created_at);
        
        // Calculate the difference between the two dates in hours
        $difference_in_hours = $due_time->diffInHours($created_at);
    
        // Define the thresholds for each expiration case in hours
        $case_1_threshold = 90;
        $case_2_threshold = 24;
        $case_3_threshold = 72;
    
        // Determine the expiration time based on the difference in hours
        if ($difference_in_hours <= $case_1_threshold) {
            return $due_time->format('Y-m-d H:i:s');
        }
        
        if ($difference_in_hours <= $case_2_threshold) {
            return $created_at->addMinutes(90)->format('Y-m-d H:i:s');
        }
        
        if ($difference_in_hours <= $case_3_threshold) {
            return $created_at->addHours(16)->format('Y-m-d H:i:s');
        }
    
        return $due_time->subHours(48)->format('Y-m-d H:i:s');
    }

}

