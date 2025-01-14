<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Facades\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Resources\StoreResource;
use App\Models\VerificationCode;

class LoginController extends Controller
{
    public function register(UserCreateRequest $request)
    {
        $phone= $request->phone;
        $verificationCode = VerificationCode::where('phone', $phone)->latest()->first();
        if (!($verificationCode && $verificationCode->code == $request->code)) {
            return response()->json(['message' => 'Invalid code'], 403);
        }
        $verificationCode->delete();
        $user = User::create([
            'phone' => $phone,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_verified_at'=>now()
        ]);
        $validated = $request->validated();
        $user->address()->create($validated['address']);
        $user = User::where('phone', $phone)->first();
        if ($request->hasFile('image')) {

            $user->photo = $this->storeFile(
                $user,
                $request->file('image'),
            );

            $user->save();
        }

        $token = $user->createToken('user_token')->plainTextToken;
        return response()->json(['message' => 'User registered Successfully', 'Token' => $token], 201);
    }
    public function login(UserLoginRequest $request)
    {
        $phone = $this->transformPhoneNumber($request->phone);
        $user = User::where('phone', $phone)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('user_token')->plainTextToken;
            return response()->json(['message' => 'User Logged Successfully', 'Token' => $token], 200);
        }
        return response()->json(['message' => 'Incorrect phone number or password'], 403);
    }

    public function logout(Request $request)
    {
        $user = request()->user();
        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'token has been deleted'], 200);
    }

    public function loginAdmin(UserLoginRequest $request){
        $phone = $this->transformPhoneNumber($request->phone);
        $user = User::where('phone', $phone)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            if($user->hasRole(Role::USER)){
                return response()->json(['message'=>'This application is for admins only'], 403);
            }
            $token = $user->createToken('user_token')->plainTextToken;
             $stores=$user->stores()->get();
            if(!count($stores)){
                return response()->json(['message'=>'User does not has any store','Token' => $token], 200);
            }
            return response()->json(['message' => 'User Logged Successfully', 'Token' => $token,'stores'=>StoreResource::collection($stores)], 200);
        }
        return response()->json(['message' => 'Incorrect phone number or password'], 403);
    }

    public function storeFile($user, $file, string $filename = null)
    {
        $path = UserController::UPLOAD_PATH . $user->id . '/';
        return FileManager::store($path, $file, $filename);
    }

    private function fileUrl($user, string $filename = null)
    {
        if ($filename == null) {
            $filename = $user->image;
        }

        $path = UserController::UPLOAD_PATH . $user->id . '/';
        return FileManager::url($path, $filename);
    }

    private function transformPhoneNumber($phoneNumber)
    {
        if (substr($phoneNumber, 0, 1) === '0') {
            return '+963' . substr($phoneNumber, 1);
        }
        return $phoneNumber;
    }
}
