<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ApiUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function getAll()
    {
        $user= User::leftJoin('company', 'users.company', '=', 'company.comp_id')
            ->leftJoin('roles','roles.role_id','users.role_id')
            ->select('users.*', 'company.*','roles.role_name')
            ->paginate(10);;
        return response()->json(
            [
                'users'=>$user,
                'status' => true,
            ],200);
    }
    public function base64ImgUpload($requestFile, $folder)
    {

        $file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $requestFile));

        $extension = explode('/', mime_content_type($requestFile))[1];


        $safeName = time().'.'.$extension;
        $success = file_put_contents(public_path().'/'.$folder.'/'.$safeName, $file);

        return  $safeName;
    }



    public function uploadImage(Request $r){

//        $file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $r['photo']));
//
//        $extension = explode('/', mime_content_type($r['photo']))[1];
//
//
//        $safeName = time().'.'.$extension;
//        $success = file_put_contents(public_path().'/uploads/'.$safeName, $file);

        $fileName=$this->base64ImgUpload($r['photo'],'profile-picture');
        //User::where('id',auth()->user()->id)->update(['profile_picture'=>$fileName]);
        //return $fileName;
        if ($fileName) {
            User::where('id', auth()->user()->id)->update(['profile_picture' => $fileName]);
            return response()->json(['status' => 200, 'message' => 'Profile picture uploaded successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Failed to upload profile picture']);
        }


    }
}
