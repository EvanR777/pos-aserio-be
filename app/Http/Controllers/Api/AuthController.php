<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Kredensial salah'], 401);
        }

        $token = $user->createToken('pos')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                'rank' => $user->rank, 'holding_id' => $user->holding_id,
                'company_id' => $user->company_id, 'outlet_id' => $user->outlet_id,
            ],
            'fe_allowed' => $this->feAllowed($user->rank),
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'user' => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'rank' => $u->rank, 'holding_id' => $u->holding_id,
                'company_id' => $u->company_id, 'outlet_id' => $u->outlet_id,
            ],
            'fe_allowed' => $this->feAllowed($u->rank),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logout ok']);
    }

    private function feAllowed(?string $rank): array
    {
        return match ($rank) {
            'A'       => ['pos'],
            'B'       => ['accounting'],
            'C', 'D'  => ['pos', 'accounting'],
            'E'       => ['pos', 'accounting', 'owner'],
            default   => ['pos'],
        };
    }
}
