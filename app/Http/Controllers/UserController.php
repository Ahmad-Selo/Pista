<?php

namespace App\Http\Controllers;

use App\Http\Requests\CodeRequest;
use App\Http\Requests\PasswordRequest;
use App\Http\Requests\PhoneRequest;
use App\Models\User;
use App\Facades\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\CheckPasswordRequest;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public const UPLOAD_PATH = 'uploads/user/';

    public function stores(User $user)
    {
        $stores = $user->stores;

        return response()->json([
            'stores' => $stores,
        ]);
    }

    public function show(Request $request)
    {
        $user = request()->user();
        $user->photo = $this->fileUrl($user);
        return response()->json($user, 200);
    }

    public function update(UserUpdateRequest $request)
    {
        $user = request()->user();
        $validated = $request->validated();
        if ($request->hasFile('image')) {
            $this->deleteFile($user);

            $login = new LoginController();
            $user->photo = $login->storeFile(
                $user,
                $request->file('image'),
            );
            $user->save();
        }
        $user->update($validated['user_info']);
        $user->address()->update($validated['address']);

        return response()->json(['message' => 'Your account updated successfully'], 200);
    }

    public function resetPassword(CheckPasswordRequest $request)
    {
        $user = request()->user();
        if (Hash::check($request->password, $user->password)) {
            $user->password = Hash::make($request->newPassword);
            $user->save();
            return response()->json(['message' => 'Your password updated successfully'], 200);
        }

        return response()->json(['message' => 'Incorrect password'], 403);
    }

    public function deleteAccount(PasswordRequest $request)
    {
        $user = request()->user();
        if (Hash::check($request->password, $user->password)) {
            $this->deleteFile($user);
            $user->tokens()->delete();
            $user->delete();
            return response()->json(['message' => 'account has been deleted'], 200);
        }
        return response()->json(['message' => 'Incorrect password'], 403);
    }

    private function deleteFile($user, string $filename = null)
    {
        if ($filename == null) {
            $filename = $user->image;
        }

        $path = self::UPLOAD_PATH . $user->id . '/';
        return FileManager::delete($path, $filename);
    }

    public function code(PhoneRequest $request)
    {
        $phone = $this->transformPhoneNumber($request->phone);
        $oldCode = VerificationCode::where('phone', $phone)->first();
        if ($oldCode) {
            $oldCode->delete();
        }
        $code = rand(1000, 9999);
        VerificationCode::create([
            'code' => $code,
            'phone' => $phone
        ]);
        // return $response = TelegramGateway::sendVerificationMessage($phone,
        // [ 'code' => $code, 'ttl' => 300, 'callback_url' => 'https://yourapp.com/callback', ]);
        $params = array(
            'token' => '7ziu5kh2gwzgcvon',
            'to' => $phone,
            'body' => 'your verification code is : ' . $code
        );
        try {
            $response = Http::asForm()->withHeaders(['Content-Type' => 'application/x-www-form-urlencoded',])
                ->post('https://api.ultramsg.com/instance103910/messages/chat', $params);
            if ($response->successful()) {
                return response()->json(
                    ['message' => 'Verification Code has send successfully'],
                    200
                );
            } else {
                return response()->json(['message' => 'Unexpected HTTP status: ' . $response->status() . ' ' . $response->reason()], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkCode(CodeRequest $request)
    {
        $phone = $this->transformPhoneNumber($request->phone);
        $code = $request->code;
        $verificationCode = VerificationCode::where('phone', $phone)->latest()->first();
        if ($verificationCode->code == $code) {
            return response()->json(['message' => 'Correct code'], 200);
        } else {
            return response()->json(['message' => 'Incorrect code'], 403);
        }
    }

    public function setNewPassword(Request $request)
    {
        $phone = $this->transformPhoneNumber($request->phone);
        $code = $request->code;
        $newPassword = $request->newPassword;
        $verificationCode = VerificationCode::where('phone', $phone)->latest()->first();
        if ($verificationCode->code == $code) {
            $user = User::where('phone', $phone)->first();
            $user->password = Hash::make($newPassword);
            $user->save();
            $verificationCode->delete();

            return response()->json(['message' => 'Password updated successfully'], 200);
        }
        return response()->json(['message' => 'Invalid code'], 403);


    }

    private function transformPhoneNumber($phoneNumber)
    {
        if (substr($phoneNumber, 0, 1) === '0') {
            return '+963' . substr($phoneNumber, 1);
        }
        return $phoneNumber;
    }

    private function fileUrl($user, string $filename = null)
    {
        if ($filename == null) {
            $filename = null;
        }

        $path = UserController::UPLOAD_PATH . $user->id . '/';
        return FileManager::url($path, $filename);
    }


}


