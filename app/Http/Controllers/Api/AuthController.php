<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware(['api'], ['except' => [
            'login'
        ]]);
    }
    public function changePassword(Request $request){
//        return $request;

        $user = User::findOrFail(auth()->user()->id);

        if(Hash::check($request->oldPassword, $user->password)){
            $user->password=Hash::make($request->password);
            $user->save();
            activity('update')
                ->causedBy(auth()->user()->id)
                ->performedOn($user)
                ->withProperties($user)
                ->log(auth()->user()->name . ' change password');

            return response()->json(['message'=>'Password Changed Successfully'],200);
        }
        else{
            return response()->json(['message'=>'Old Password Does not match'],203);
        }
    }
    public function register(Request $request){
        if(auth()->user()->role_id>1){
            return response()->json(['message'=>'Access Forbidden'],403);
        }

        $validator=Validator::make($request->all(),[
            'name'=>'required|string',
            'email'=>'required|string|unique:users',
            'phone'=>'required',
            'password'=>'required|string',
            'role_id'=>'required',
            'company_id'=>'required'
        ]);


        if($validator->fails()){
            return response()->json($validator->errors(),400);
        }


//        $user=User::create([
//            'name'=>$request->name,
//            'email'=>$request->email,
//            'password'=>Hash::make($request->password)
//        ]);
        $user=new User();
        $user->name=$request->name;
        $user->email=$request->email;
        $user->phone=$request->phone;
        $user->password=Hash::make($request->password);
        $user->role_id=$request->role_id;
        $user->company=$request->company_id;
        $user->save();

        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($user)
            ->withProperties($user)
            ->log(auth()->user()->name . ' created user');

        return response()->json([
            'message'=>'registered',
            'user'=>$user
        ]);
    }
    // public function login(Request $request){
    //     $validator=Validator::make($request->all(),[
    //         'email'=>'required|string',
    //         'password'=>'required|string',
    //     ]);
    //     if($validator->fails()){
    //         return response()->json($validator->errors(),400);
    //     }

    //     if(!$token=auth()->attempt($validator->validated())){
    //         return response()->json(['error'=>'Unauthorized']);
    //     }
    //     return $this->respondWithToken($token);
    // }

    public function users(){

        $users=User::leftJoin('roles','roles.role_id','users.role_id')
            ->leftJoin('company','company.comp_id','users.company');


        if( auth()->user()->role_id==2){
            $users=$users->where('company',auth()->user()->company);
        }
        elseif (auth()->user()->role_id>2){
            return response(['message' => 'Access Forbidden'],403);
        }
        $users=$users->paginate();

        return response()->json(['users' => $users], 200);
    }
    public function logout(){
        auth()->logout();
//        JWTAuth::parseToken()->invalidate(); // Invalidate the JWT token

        return response()->json(['message' => 'Logged out successfully']);
// Pass true to force the token to be blacklisted "forever"
//        auth()->logout(true);
    }
    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)
             ->orWhere('phone', $request->email)
            ->first();
//             return response()->json(['message'=>$user->password]);



        if ($user &&
            Hash::check($request->password, $user->password)) {
            $token = $this->guard('api')->login($user);
            $u= auth('api')->user();
//            $u=['user'=>$u];
            $t=$this->respondWithToken($token);
            $company=Company::where('comp_id',auth('api')->user()->company)->first();
            $employee=Employee::with(['designation', 'branch', 'department'])->where('user_id',auth('api')->user()->id)->first();
            return response()->json(['access_token' => $token,'user'=>$u,'employee'=>$employee,'company'=>$company], 200);
//            return $this->respondWithToken($token);
        }

   //        if ($token = $this->guard('api')->attempt($credentials)) {
//
//            return $this->respondWithToken($token);
//        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    protected function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
//            'expires_in' => $this->guard('api')->factory()->getTTL() * 60*60*100*1000
            'expires_in' => false
        ]);
    }
    public function guard()
    {
        return Auth::guard('api');
    }
    public function dashboard(){
        return response()->json(auth()->user());
    }



}
