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
        if(auth()->user()->role_id>1){
            return  response()->json(['message'=>'Access Forbidden'],403);
        }
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
//        $fileSize = strlen($file);

        // Check if the file size exceeds 2 MB (2 * 1024 * 1024 bytes)
        if (mb_strlen($file, '8bit') > 2 * 1024 * 1024) {
            return false; // Return false indicating file exceeds the size limit
        }


        $extension = explode('/', mime_content_type($requestFile))[1];


        $safeName = time().'.'.$extension;
        $success = file_put_contents(public_path().'/'.$folder.'/'.$safeName, $file);
        return $success ? $safeName : false;
//        return  $safeName;
    }



//    public function uploadImage(Request $r){
//
////        $file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $r['photo']));
////
////        $extension = explode('/', mime_content_type($r['photo']))[1];
////
////
////        $safeName = time().'.'.$extension;
////        $success = file_put_contents(public_path().'/uploads/'.$safeName, $file);
//
//        $fileName=$this->base64ImgUpload($r['photo'],'profile-picture');
//        //User::where('id',auth()->user()->id)->update(['profile_picture'=>$fileName]);
//        //return $fileName;
//        if ($fileName) {
//            $userId = auth()->user()->id;
//            User::where('id', auth()->user()->id)->update(['profile_picture' => $fileName]);
//            activity('update')
//                ->causedBy($userId)
//                ->performedOn(auth()->user()) // Assuming you want to log the activity on the user model
//                ->withProperties(['profile_picture' => $fileName]) // Adding profile_picture to properties
//                ->log(auth()->user()->name . ' updated profile picture');
//
//            return response()->json(
//                [
//                    'status' => 200,
//                    'message' => 'Profile picture uploaded successfully',
//                    'profile_picture'=>$fileName
//                ]
//            );
//        } else {
//            return response()->json(['status' => 500, 'message' => 'File size exceeds the limit or Failed to upload profile picture']);
//        }
//    }
    public function uploadImage(Request $r){
        $fileName = $this->base64ImgUpload($r['photo'], 'profile-picture');

        if ($fileName === false) {
            return response()->json(['status' => 500, 'message' => 'File size exceeds the limit or Failed to upload profile picture']);
        }

        $userId = auth()->user()->id;

        // Attempt to update the user's profile picture
        $updateResult = User::where('id', $userId)->update(['profile_picture' => $fileName]);

        if ($updateResult) {
            activity('update')
                ->causedBy($userId)
                ->performedOn(auth()->user())
                ->withProperties(['profile_picture' => $fileName])
                ->log(auth()->user()->name . ' updated profile picture');

            return response()->json([
                'status' => 200,
                'message' => 'Profile picture uploaded successfully',
                'profile_picture' => $fileName
            ]);
        } else {
            // Handle the case when the update in the database fails
            // Provide an appropriate error message or take necessary action
            return response()->json(['status' => 500, 'message' => 'Failed to update profile picture in the database']);
        }
    }

//    public function uploadSign(Request $r){
//        $fileName=$this->base64ImgUpload($r['sign'],'signature');
//        //User::where('id',auth()->user()->id)->update(['profile_picture'=>$fileName]);
//        //return $fileName;
//        if ($fileName) {
//            $userId = auth()->user()->id;
//            User::where('id', auth()->user()->id)->update(['signature' => $fileName]);
//            activity('update')
//                ->causedBy($userId)
//                ->performedOn(auth()->user()) // Assuming you want to log the activity on the user model
//                ->withProperties(['profile_picture' => $fileName]) // Adding profile_picture to properties
//                ->log(auth()->user()->name . ' updated signature');
//
//            return response()->json(
//                [
//                    'status' => 200,
//                    'message' => 'Signature uploaded successfully',
//                    'signature' => $fileName
//                ]
//            );
//        } else {
//            return response()->json(['status' => 500, 'message' => 'File size exceeds the limit or failed to upload signature']);
//        }
//    }
    public function uploadSign(Request $r){
        $fileName = $this->base64ImgUpload($r['sign'], 'signature');

        if ($fileName === false) {
            return response()->json(['status' => 500, 'message' => 'File size exceeds the limit or Failed to upload signature']);
        }

        $userId = auth()->user()->id;

        // Attempt to update the user's signature
        $updateResult = User::where('id', $userId)->update(['signature' => $fileName]);

        if ($updateResult) {
            activity('update')
                ->causedBy($userId)
                ->performedOn(auth()->user())
                ->withProperties(['signature' => $fileName]) // Adjusted property to 'signature'
                ->log(auth()->user()->name . ' updated signature');

            return response()->json([
                'status' => 200,
                'message' => 'Signature uploaded successfully',
                'signature' => $fileName
            ]);
        } else {
            // Handle the case when the update in the database fails
            // Provide an appropriate error message or take necessary action
            return response()->json(['status' => 500, 'message' => 'Failed to update signature in the database']);
        }
    }

}
