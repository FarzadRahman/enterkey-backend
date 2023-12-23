<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
class ApiCompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api']);
    }
    public function edit($id){
        $company = Company::findOrFail($id);
        return response()->json(['data' => $company], 200);
    }
    public function store(Request $request)
    {

//        return $request;

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_address'=>'required|string',
            'contact_number'=>'required|string',
            'contact_person'=>'required|string',
            'contact_email'=>'required|string',
            'company_bin'=>'required',
            'company_tin'=>'required'

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data


//        $company = Company::create($data);

        $company=new Company();
        $company->company_name=$request->company_name;
        $company->contact_address=$request->contact_address;
        $company->contact_number=$request->contact_number;
        $company->contact_person=$request->contact_person;
        $company->contact_email=$request->contact_email;
        $company->company_bin=$request->company_bin;
        $company->company_tin=$request->company_tin;
        $company->save();

        activity('create')
            ->causedBy(auth()->user()->id)
            ->performedOn($company)
            ->withProperties($company)
            ->log(auth()->user()->name . ' created company');
        return response()->json(['message' => 'Company created successfully', 'data' => $company], 201);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_address'=>'required|string',
            'contact_number'=>'required|string',
            'contact_person'=>'required|string',
            'contact_email'=>'required|string',
            'company_bin'=>'required',
            'company_tin'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        $company->company_name=$request->name;
        $company->contact_address=$request->contact_address;
        $company->contact_number=$request->contact_number;
        $company->contact_person=$request->contact_person;
        $company->contact_email=$request->contact_email;
        $company->company_bin=$request->company_bin;
        $company->company_tin=$request->company_tin;
        $company->save();
        activity('update')
            ->causedBy(auth()->user()->id)
            ->performedOn($company)
            ->withProperties($company)
            ->log(auth()->user()->name . ' updated company');
        return response()->json(['message' => 'Company updated successfully', 'data' => $company], 200);
    }
    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        $branches = Branch::where('company_id', $id)->get();

        if ($branches->count() > 0) {
            return response()->json(['message' => 'Company cannot be deleted because it has associated branches'], 200);
        }

        $company->delete();
        activity('delete')
            ->causedBy(auth()->user()->id)
            ->performedOn($company)
            ->withProperties($company)
            ->log(auth()->user()->name . ' delete company');

        return response()->json(['message' => 'Company deleted successfully'], 200);
    }


    public function getAll(){

        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => 'Login first'], 401);
        }
        $companies=Company::select('*');

        if( auth()->user()->role_id>=2){
            $companies=$companies->where('comp_id',auth()->user()->company);
        }

        $companies=$companies->paginate(10);

        return $companies;
    }
}
