<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);
        if($validator->fails()){
            return response(['errors'=>$validator->errors()],403);
        }
        $request['password'] =Hash::make($request['password']);

        $user = User::create($request->all());
        $authToken = $user->createToken('authToken')->accessToken;
        return response()->json(['user'=>$user,'access_token'=>$authToken],200);
    }
    public function login(Request $request){
        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6'
        ]);
        if($validator->fails()){
            return response(['errors'=>$validator->errors()],403);
        }
        if(!auth()->attempt($request->all())){
            return response()->json(['messaage'=>'invalid credentials']);
        }
        $accessToken = auth()->user()->createToken('authToken')->accessToken;
        return response()->json(['user' => auth()->user(), 'access_token' => $accessToken],200);


    }

    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
