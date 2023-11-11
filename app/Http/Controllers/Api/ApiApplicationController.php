<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }

    public function store(Request $request){
        return $request;
        $Sdate = Carbon::parse($request->start)->startOfDay();
        $edate = Carbon::parse($request->end)->endOfDay();
        $days = $Sdate->diffInDays($edate);
        $total_days = $days + 1;

//        $leave=Application::where('employee_id',$request->employee_id)->where('start',$request->start)->where('end',$request->end)->first();
        if ($request->end) {
            $endDate = $request->end;
        } else {
            $endDate = $request->start;
        }

        $emp = Employee::where('user_id', auth('api')->user()->id)
            ->leftJoin('designations', 'designations.designation_id', 'employees.designation')
            ->first();

        $leave = Application::where('employee_id', $emp->id)
            ->where('approved_end_date', '>=', $request->start)
            ->where('approved_start_date', '<=', $endDate)->first();

        $chkLeavePending = Application::where('employee_id', $emp->id)
            ->where('start_date', '>=', $request->start)
            ->where('end_date', '<=', $endDate)
            ->where('status',1)
            ->first();

        if ($total_days > 10) {

            return response()->json([
                'message' => 'সর্বোচ্চ ১০ দিন ছুটি নিতে পারবেন',

            ]);

        }

        if ($chkLeavePending != null) {

            return response()->json([
                'message' => 'উক্ত তারিখে ছুটির আবেদন পূর্বের ছুটি অপেক্ষামান রয়েছে!',

            ]);

        }

        if ($leave != null) {

            return response()->json([
                'message' => 'উক্ত তারিখে ছুটির আবেদন পূর্বে গৃহীত করা রয়েছে!',

            ]);

        } else {



            $application = new Application();

            $application->approval_id = Employee::where('user_id',$request->admin_id)->first()->id;

            $application->employee_id = Employee::where('user_id',auth('api')->user()->id)->first()->id;


            $application->reason = $request->reason;
            $application->stay_location = $request->stay;
            $application->start_date = $request->start;
            $application->end_date = $request->end;
            if ($total_days == 1) {
                $application->end_date = $request->start;
            }
            $application->applied_total_days = $total_days;
            $application->approved_total_days = 0;
            $application->status = 1;
            $application->save();

            return response()->json([
                'message' => ' Application Submitted Successfully',

            ]);
        }
    }
}
