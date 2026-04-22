<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $user = GsUser::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['ok' => false, 'msg' => 'Invalid credentials'], 401);
        }

        // لو عندك شرط active
        if (($user->active ?? '') !== 'true') {
            return response()->json(['ok' => false, 'msg' => 'User is inactive'], 403);
        }

        $dbPass = (string) $user->password;
        $inputPass = (string) $request->password;

        $isMd5 = (strlen($dbPass) === 32) && ctype_xdigit($dbPass);

        $valid = $isMd5
            ? (md5($inputPass) === strtolower($dbPass))
            : Hash::check($inputPass, $dbPass);

        if (!$valid) {
            return response()->json(['ok' => false, 'msg' => 'Invalid credentials'], 401);
        }

        // امسح توكنات قديمة لو عايز (اختياري)
        // $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'ok' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'ok' => true,
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    public function getSliderSlides()
    {
        $slides = [
            [
                'title' => 'FleetRite',
                'description' => 'Automate Mail & Parcels delivery operations via our innovative post management solution which provides real-time tracking, automated dispatching',
                'image' => null
            ],
            [
                'title' => 'Smart Logistics',
                'description' => 'Optimize your fleet performance with our advanced analytics and real-time monitoring tools.',
                'image' => null
            ],
            [
                'title' => 'Global Connectivity',
                'description' => 'Stay connected with your assets anywhere in the world with our reliable tracking network.',
                'image' => null
            ]
        ];

        return response()->json([
            'ok' => true,
            'slides' => $slides
        ]);
    }
}
