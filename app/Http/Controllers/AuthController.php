<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\Member;
use App\Mail\MailableGym;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use DateInterval;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request) {
        try {
            if (!$request->has(['email', 'password'])) {
                return response()->json([
                    'data' => [],
                    'message' => 'El email y la contraseña son requeridos',
                    'success' => false
                ]);    
            }

            $rules = [
                'email' => 'required|email',
                'password' => ['required', Password::min(6)->mixedCase()->symbols()->numbers()]
            ];

            $messages = [
                'email.required' => 'El email es requerido',
                'email.email' => 'El email no cumple el formato requerido',
                'password.required' => 'La contraseña es requerida',
                'password.min' => 'Mínimo 6 carácteres para la contraseña',
                'password.mixed' => 'Mínimo 1 mayúscula y minúscula para la contraseña',
                'password.symbols' => 'Mínimo 1 símbolo para la contraseña',
                'password.numbers' => 'Mínimo 1 número para la contraseña',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $formatErrors = collect($validator->errors())->map(function($error) {
                    return $error[0];
                });

                return response()->json([
                    'data' => [],
                    'message' => 'Errores de la petición',
                    'errors' => $formatErrors,
                    'success' => false
                ]);
            }

            $userExist = User::where('email', $request->string('email')->trim())->first();
            if (!$userExist) {
                return response()->json([
                    'data' => [],
                    'message' => 'Email y/o contraseña incorrectos.',
                    'success' => false
                ]);
            }

            if (!Hash::check($request->string('password')->trim(), $userExist->password)) {
                return response()->json([
                    'data' => [],
                    'message' => 'Email y/o contraseña incorrectos.',
                    'successs' => 'false'
                ]);
            }

            $token = $userExist->createToken($request->string('email')->trim())->plainTextToken;
            return response()->json([
                'data' => $userExist,
                'message' => 'Login éxitoso',
                'success' => true,
                'token' => $token
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al ejecutar la petición',
                'success' => false
            ]);
        }
    }

    public function register(Request $request) {
        try {
            $rules = [
                'name' => 'required|min:3',
                'email' => 'required|email',
                'password' => ['required', Password::min(6)->mixedCase()->symbols()->numbers()]
            ];

            $messages = [
                'name.required' => 'El nombre es requerido',
                'name.min' => 'Mínimo 3 carácteres para el nombre',
                'email.required' => 'El email es requerido',
                'email.email' => 'El email no cumple el formato requerido',
                'password.required' => 'La contraseña es requerida',
                'password.min' => 'Mínimo 6 carácteres para la contraseña',
                'password.mixed' => 'Mínimo 1 mayúscula y minúscula para la contraseña',
                'password.symbols' => 'Mínimo 1 símbolo para la contraseña',
                'password.numbers' => 'Mínimo 1 número para la contraseña',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $formatErrors = collect($validator->errors())->map(function($error) {
                    return $error[0];
                });

                return response()->json([
                    'data' => [],
                    'message' => 'Errores de la petición',
                    'errors' => $formatErrors,
                    'success' => false
                ]);
            }

            $userExist = User::where('email', $request->string('email')->trim())->first();
            if ($userExist) {
                return response()->json([
                    'data' => [],
                    'message' => 'El usuario ya está registrado',
                    'success' => false
                ]);
            }

            $newUser = new User;
            $newUser->name = $request->string('name')->trim();
            $newUser->email = $request->string('email')->trim();
            $newUser->password = Hash::make($request->string('password')->trim());

            $newUser->save();

            $token = $newUser->createToken($request->string('email')->trim())->plainTextToken;

            return response()->json([
                'data' => [],
                'message' => 'Usuario creado',
                'success' => true,
                'token' => $token
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al crear el usuario',
                'success' => false
            ]);
        }
    }

    public function activityInformationFromHome() {
        try {
            $results = [];
            $quantityMembers = Member::where('state', 'ACTIVE')->count();
            $results['quantityMembers'] = $quantityMembers || 0;

            $quantityMemberships = Membership::where('state', 'ACTIVE')->count();
            $results['quantityMemberships'] = $quantityMemberships || 0;
            
            $now = date("Y-m-d");
            $currentDate = date("Y-m-d", strtotime($now . " +10 days"));
            $closeToExpiring = Membership::where('state', 'ACTIVE')->whereBetween('end_date', [$now, $currentDate])->count();
            $results['quntityCloseToExpiring'] = $closeToExpiring || 0;

            return response()->json([
                'data' => $results,
                'message' => 'Estadisticas de membresias listadas',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al ejecutar la petición',
                'success' => false
            ]);
        }
    }

    public function sendEmailResetPassword(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|exists:users,email'
            ], [
                'email.required' => 'El email es obligatorio.',
                'email.exists' => 'Verifica el correo ingresado.'
            ]);
            
            if ($validator->fails()) {
                $formatErrors = collect($validator->errors())->map(function($error) {
                    return $error[0];
                });
                
                return response()->json([
                    'data' => [],
                    'message' => 'Errores de la petición',
                    'errors' => $formatErrors,
                    'success' => false
                ]);
            }
            
            $token = rand(100000, 999999);

            DB::table('password_reset_tokens')->where(['email' => $request->input('email')])->delete();
            
            DB::table('password_reset_tokens')->insert([
                'email' => $request->input('email'),
                'token' => $token,
                'created_at' => now()
            ]);
            
            Mail::to($request->input('email'))->send(new MailableGym($token));

            return response()->json([
                'data' => [],
                'message' => 'El correo fue enviado, por favor revisa tu bandeja o el spam.',
                'success' => true,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'No se pudo enviar el correo.',
                'success' => false
            ]);
        }
    }

    public function validateCodePassword(Request $request) {
        try {
            $request->merge(['code' => $request->route('code')]);

            $validator = Validator::make($request->all(), [
                'code' => 'required|digits:6'
            ], [
                'code.required' => 'El codigo es requerido.',
                'code.digits' => 'El codigo no cumple con la cantidad de carácteres.'
            ]);
            
            if ($validator->fails()) {
                $formatErrors = collect($validator->errors())->map(function($error) {
                    return $error[0];
                });
                
                return response()->json([
                    'data' => [],
                    'message' => 'Errores de la petición',
                    'errors' => $formatErrors,
                    'success' => false
                ]);
            }

            $data = DB::table('password_reset_tokens')->where(['token' => $request->input('code')])->first();

            if ($data) {
                $now = new DateTime();
                $originalDate = new DateTime($data->created_at);
                $originalDate->add(new DateInterval('PT10M'));

                if ($now > $originalDate) {
                    return response()->json([
                        'data' => [],
                        'message' => 'El código ya expiró.',
                        'success' => false
                    ]);
                }

                return response()->json([
                    'data' => [],
                    'message' => 'El código es válido.',
                    'success' => true
                ]);
            }

            return response()->json([
                'data' => [],
                'message' => 'El código no es válido.',
                'success' => false
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'No se pudo realizar esta acción.',
                'success' => false
            ]);
        }
    }

    public function changePassword(Request $request) {
        try {
            $rules = [
                'token' => 'required|digits:6|exists:password_reset_tokens,token',
                'password' => ['required', Password::min(6)->mixedCase()->symbols()->numbers()],
                'email' => 'required|email'
            ];

            $messages = [
                'token.required' => 'El código es requerido',
                'token.digits' => 'Verifica el código enviado.',
                'token.exists' => 'Verifica el código enviado.',
                'email.required' => 'El email es requerido',
                'email.email' => 'El email no cumple el formato requerido',
                'password.required' => 'La contraseña es requerida',
                'password.min' => 'Mínimo 6 carácteres para la contraseña',
                'password.mixed' => 'Mínimo 1 mayúscula y minúscula para la contraseña',
                'password.symbols' => 'Mínimo 1 símbolo para la contraseña',
                'password.numbers' => 'Mínimo 1 número para la contraseña',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $errorsFormat = collect($validator->errors())->map(function($error) {
                    return $error[0];
                });

                return response()->json([
                    'data' => [],
                    'message' => 'Errores de la petición',
                    'errors' => $errorsFormat,
                    'success' => false
                ]);
            }

            $userExist = User::where('email', $request->input('email'))->first();
            if (!$userExist) {
                return response()->json([
                    'data' => [],
                    'message' => 'No se pudo actualizar la contraseña, intenta de nuevo',
                    'success' => false
                ]);
            }

            $userExist->update([
                'password' => Hash::make($request->input('password'))
            ]);

            return response()->json([
                'data' => [],
                'message' => 'Contraseña actualizada.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'No se pudo realizar esta acción.',
                'success' => false
            ]);
        }
    }
}
