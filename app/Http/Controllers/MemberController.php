<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    /**
     * Muestra una lista de usuarios
     */
    public function index(Request $request): AnonymousResourceCollection | JsonResponse
    {
        try {
            if (!$request->hasAny(['state', 'query'])) {
                $members = Member::with(['memberships' => function (Builder $query) {
                    $query->orderBy('id', 'desc');
                }])->where('state', 'ACTIVE')->orderBy('id', 'desc')->paginate(10);

                return MemberResource::collection($members)->additional([
                    'message' => 'Usuarios listados',
                    'success' => true
                ]);
            }

            $filterData = Member::query();
            if ($request->filled('state')) {
                $filterData->where('state', $request->string('state')->trim());
            } else {
                $filterData->where('state', 'ACTIVE')
                    ->where(function ($q) use ($request) {
                        $query = trim($request->string('query'));
                        $likeQuery = "%{$query}%";

                        $q->whereRaw("CONCAT(name, ' ', last_name) LIKE ?", [$likeQuery])
                            ->orWhere('phone', 'LIKE', [$likeQuery])
                            ->orWhere('registration_date', 'LIKE', [$likeQuery])
                            ->orWhere('hour', 'LIKE', [$likeQuery]);
                    });
            }
    
            return MemberResource::collection($filterData->with('memberships')->orderBy('id', 'desc')->paginate(10))->additional([
                'message' => 'Usuarios listados',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al filtrar los usuarios',
                'success' => $th
            ]);
        }
    }

    /**
     * Crea un nuevo usuario.
     */
    public function store(Request $request)
    {
        try {
            $rules = [
                'name' => 'required|min:3|max:40',
                'last_name' => 'required|min:3|max:40',
                'phone' => 'required|digits:10',
                'registration_date' => ['required', Rule::date()->format('Y-m-d')],
                'hour' => ['required', Rule::date()->format('H:i')]
            ];
    
            $messages = [
                'name.required' => 'El nombre es requerido',
                'last_name.required' => 'El apellido es requerido',
                'phone.required' => 'El celular es requerido',
                'phone.digits' => 'El celular debe tener exactamente 10 dígitos',
                'registration_date.required' => 'La fecha de registro es requerida',
                'hour.required' => 'La hora es requerida',
                'min' => 'Mínimo 3 carácteres',
                'max' => 'Máximo 40 carácteres',
                'registration_date.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
                'hour.required' => 'La hora es requerida.',
                'hour.date_format' => 'La hora debe tener el formato HH:MM en horario de 24 horas.',
                'state.required' => 'El estado es obligatorio.',
                'state.in' => 'El estado debe ser "ACTIVE" o "INACTIVE".',
            ];
    
            $validator = Validator::make($request->all(), $rules, $messages);
    
            if ($validator->fails()) {
                $formatMessages = collect($validator->errors()->messages())->map(function ($error) {
                    return $error[0];
                }); 
    
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible crear el usuario',
                    'success' => false,
                    'errors' => $formatMessages
                ]);
            }
    
            $validated = $validator->validated();
    
            $member = new Member;
    
            $member->name = $validated['name'];
            $member->last_name = $validated['last_name'];
            $member->phone = $validated['phone'];
            $member->registration_date = $validated['registration_date'];
            $member->hour = $validated['hour'];
            $member->state = 'ACTIVE';
    
            $member->save();

            return response()->json([
                'data' => $member,
                'message' => 'Usuario creado con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al crear el usuario',
                'success' => false
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            if (!isset($id)) {
                return response()->json([
                    'data' => [],
                    'message' => 'No se encontró el recurso especificado',
                    'success' => false
                ]);
            }

            $member = Member::where('id', $id)->with('memberships')->get();
            if (!$member) {
                return response()->json([
                    'data' => [],
                    'message' => 'No se encontró el recurso especificado',
                    'success' => false
                ]);
            }

            return MemberResource::collection($member)->additional([
                'message' => 'Usuario listado',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al mostrar el usuario',
                'success' => false
            ]);
        }
    }

    /**
     * Actualiza un usuario.
     */
    public function update(Request $request, string $id)
    {
        try {
            if (!$request->hasAny(['name', 'last_name', 'phone', 'registration_date', 'hour']) || !isset($id)) {
                return response()->json([
                    'data' => [],
                    'message' => 'Los parámetros enviados no están dentro de los permitidos',
                    'success' => false
                ]);
            }

            $member = Member::where('id', $id)->first();
            if (!$member) {
                return response()->json([
                    'data' => [],
                    'message' => 'El usuario especificado no existe',
                    'success' => false
                ]);
            }

            $dataUpdate = [];
            $rules = [];
            $messages = [
                'min' => 'Mínimo 3 carácteres',
                'max' => 'Máximo 40 carácteres',
            ];

            if ($request->filled('name')) {
                $dataUpdate['name'] = $request->string('name')->trim();
                $rules['name'] = 'min:3|max:40';
            }
    
            if ($request->filled('last_name')) {
                $dataUpdate['last_name'] = $request->string('last_name')->trim();
                $rules['last_name'] = 'min:3|max:40';
            }

            if ($request->filled('phone')) {
                $dataUpdate['phone'] = $request->string('phone')->trim();
                $rules['phone'] = 'digits:10';
            }
    
            if ($request->filled('registration_date')) {
                $dataUpdate['registration_date'] = $request->string('registration_date')->trim();
                $rules['registration_date'] = Rule::date()->format('Y-m-d');
                $messages['registration_date.date_format'] = 'La fecha debe tener el formato YYYY-MM-DD.';
            }
    
            if ($request->filled('hour')) {
                $dataUpdate['hour'] = $request->string('hour')->trim();
                $rules['hour'] = Rule::date()->format('H:i');
                $messages['hour.date_format'] = 'La hora debe tener el formato HH:MM en horario de 24 horas.';
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $errorsFormat = collect($validator->errors())->map(function($error) {
                    return $error[0];
                });
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible actualizar el usuario',
                    'success' => false,
                    'errors' => $errorsFormat
                ]);
            }
            
            $memberUpdate = $member->update($dataUpdate);
            return response()->json([
                'data' => $dataUpdate,
                'message' => 'Usuario actualizado con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al editar el usuario',
                'success' => false
            ]);
        }
    }

    /**
     * Cambia el estado de un usuario a INACTIVE.
     */
    public function destroy(string $id)
    {
        try {
            if (!isset($id)) {
                return response()->json([
                    'data' => [],
                    'message' => 'Ocurrió un error al eliminar el usuario',
                    'success' => false
                ]);
            }

            $memberActive = Member::where('id', $id)->where('state', 'ACTIVE')->first();
            if (!$memberActive) {
                return response()->json([
                    'data' => [],
                    'message' => 'No se encontró el usuario especificado',
                    'success' => false
                ]);
            }

            $memberActive->update(['state' => 'INACTIVE']);

            return response()->json([
                'data' => [],
                'message' => 'Usuario eliminado con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al eliminar el usuario',
                'success' => false
            ]);
        }
    }
}
