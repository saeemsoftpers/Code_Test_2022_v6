<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function index(Request $request)
    {
        // Use strict comparison operator to check if user_id is set and not null
        if($request->has('user_id') && $request->get('user_id') !== null) {
            // Use a more descriptive variable name for the response
            $userJobs = $this->repository->getUsersJobs($request->get('user_id'));
            return response($userJobs);
        }
        // Use boolean logic to check if user is an admin or superadmin
        elseif($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || 
               $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            // Use a more descriptive variable name for the response
            $allJobs = $this->repository->getAll($request);
            return response($allJobs);
        }
        
        // Return an error response if neither condition is met
        return response(['error' => 'Invalid request.'], 400);
    }
    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        // /Use the except method to remove the unnecessary fields _token and
        //  submit from the request data.
        $data = $request->except('_token', 'submit');
    $currentUser = $request->__authenticatedUser;
    $response = $this->repository->updateJob($id, $data, $currentUser);

    return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if ($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }
    //no need to return null at the end of the function as it is unnecessary. 
    // It is better to return a default response or an empty response instead of null
        return response()->json([]);
    }    

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        // Get all the data from the request
        $data = $request->all();
    
        // Set default values for distance, time, and admin comment
        $distance = $data['distance'] ?? "";
        $time = $data['time'] ?? "";
        $adminComment = $data['admincomment'] ?? "";
    
        // Set default values for job ID, session time, flagged status, manually handled status, and by admin status
        $jobId = $data['jobid'] ?? null;
        $sessionTime = $data['session_time'] ?? "";
        $flagged = $data['flagged'] === 'true' ? 'yes' : 'no';
        $manuallyHandled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
        $byAdmin = $data['by_admin'] === 'true' ? 'yes' : 'no';
    
        // If the flagged status is set to 'yes', make sure the admin comment is not empty
        if ($flagged === 'yes' && empty($adminComment)) {
            return response('Please add a comment', 400);
        }
    
        // Update the distance and time for the specified job ID
        if ($jobId && ($time || $distance)) {
            $affectedRows = Distance::where('job_id', '=', $jobId)->update([
                'distance' => $distance,
                'time' => $time,
            ]);
        }
    
        // Update the admin comment, session time, flagged status, manually handled status, and by admin status for the specified job ID
        if ($jobId && ($adminComment || $sessionTime || $flagged || $manuallyHandled || $byAdmin)) {
            $affectedRows1 = Job::where('id', '=', $jobId)->update([
                'admin_comments' => $adminComment,
                'flagged' => $flagged,
                'session_time' => $sessionTime,
                'manually_handled' => $manuallyHandled,
                'by_admin' => $byAdmin,
            ]);
        }
    
        // Return a success response
        return response('Record updated!');
    }
    
    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
