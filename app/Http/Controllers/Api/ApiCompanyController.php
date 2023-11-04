<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
class ApiCompanyController extends Controller
{
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data


//        $company = Company::create($data);

        $company=new Company();
        $company->company_name=$request->name;
        $company->contact_address=$request->contact_address;
        $company->contact_number=$request->contact_number;
        $company->contact_person=$request->contact_person;
        $company->contact_email=$request->contact_email;
        $company->company_bin=$request->company_bin;
        $company->company_tin=$request->company_tin;
        $company->save();

        return response()->json(['message' => 'Company created successfully', 'data' => $company], 201);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = $validator->validated(); // Retrieve the validated data

        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company->update($data);

        return response()->json(['message' => 'Company updated successfully', 'data' => $company], 200);
    }
    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company->delete();

        return response()->json(['message' => 'Company deleted successfully'], 200);
    }


    public function getAll(){
        $companies=Company::get();

        return $companies;
    }
}
