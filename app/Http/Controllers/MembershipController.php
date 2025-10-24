<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipResource;
use App\Models\Member;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class MembershipController extends Controller
{
    /**
     * Permite listar por filtros las membresias.
     */
    public function index(Request $request): JsonResponse | AnonymousResourceCollection
    {
        try {
            if (!$request->hasAny(['state', 'query'])) {
                $memberships = Membership::with('member')->paginate(10);
                return MembershipResource::collection($memberships)->additional([
                    'message' => 'Membresias listadas',
                    'success' => true
                ]);
            }

            $query = Membership::query();
            if ($request->filled('state')) {
                $query->where('state', $request->string('state')->trim());
            } else {
                $query->where('state', 'ACTIVE')
                    ->where(function($q) use ($request) {
                        $query = trim($request->string('query'));
                        $likeQuery = "%{$query}%";

                        $q->orWhere('start_date', 'LIKE', $likeQuery)
                            ->orWhere('end_date', 'LIKE', $likeQuery)
                            ->orWhere('pay', 'LIKE', $likeQuery)
                            ->orWhereHas('member', function ($q2) use ($likeQuery) {
                                $q2->whereRaw("CONCAT(name, ' ', last_name) LIKE ?", $likeQuery);
                            });
                    });
            }

            return MembershipResource::collection($query->with('member')->paginate(10))->additional([
                'message' => 'Membresias listados',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al filtrar las membresias',
                'success' => false
            ]);
        }
    }

    /**
     * Crea una nueva membresia.
     */
    public function store(Request $request): JsonResponse | AnonymousResourceCollection
    {
        try {
            $rules = [
                'user' => 'exists:App\Models\User,id',
                'start_date' => ['required', Rule::date()->format('Y-m-d')],
                'end_date' => ['required', Rule::date()->format('Y-m-d'), 'after:start_date'],
                'pay' => ['required', Rule::in(['Debe', 'Pagado'])],
                'balance' => ['integer'],
            ];

            $messages = [
                'user.exists' => 'El usuario especificado, no existe.',
                'start_date.required' => 'La fecha de inicio de la membresia es requerida',
                'start_date.date_format' => 'La fecha de inicio de la membresia no cumple con el formato (AAAA-MM-DD)',
                'end_date.required' => 'La fecha de fin de la membresia es requerida',
                'end_date.date_format' => 'La fecha de fin de la membresia no cumple con el formato (AAAA-MM-DD)',
                'end_date.after' => 'La fecha de fin de la membresia debe ser mayor a la de inicio',
                'pay.required' => 'El estado del pago es requerido',
                'pay.in' => 'El estado del pago solo puede ser (Pagado, Debe)',
                'balance.integer' => 'El saldo deben ser solo números'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $formatMessages = collect($validator->errors()->messages())->map(function($error) {
                    return $error[0];
                });
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible crear el usuario',
                    'success' => false,
                    'errors' => $formatMessages
                ]);
            }

            $validatedData = $validator->validated();

            $membership = new Membership;

            $membership->user_id = $validatedData['user'];
            $membership->start_date = $validatedData['start_date'];
            $membership->end_date = $validatedData['end_date'];
            $membership->pay = $validatedData['pay'];
            $membership->state = 'ACTIVE';

            if (isset($validatedData['balance'])) {
                $membership->balance = $validatedData['balance'];
            }

            $membership->save();

            return response()->json([
                'data' => $membership,
                'message' => 'Membresia creada con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al crear la membresia',
                'success' => false
            ]);
        }
    }

    /**
     * Muestra una membresia en espcifico.
     */
    public function show(string $id)
    {
        try {
            if (!isset($id)) {
                return response()->json([
                    'data' => [],
                    'message' => 'Es necesario el identificador de la membresia',
                    'success' => false
                ]);
            }

            $membership = Membership::where('id', $id)->with('user')->get();
            if (!$membership) {
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible encontrar la membresia. Intente nuevamente.',
                    'success' => false
                ]);
            }

            return MembershipResource::collection($membership)->additional([
                'message' => 'Membresia encontrada',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al consultar la membresia',
                'success' => false
            ]);
        }
    }

    /**
     * Actualiza una membresia.
     */
    public function update(Request $request, string $id)
    {
        try {
            if (!$request->hasAny(['user', 'start_date', 'end_date', 'pay', 'balance']) || !isset($id)) {
                return response()->json([
                    'data' => [],
                    'message' => 'Los parámetros enviados no están dentro de los permitidos',
                    'success' => false
                ]);    
            }

            $membership = Membership::where('id', $id)->first();
            if (!$membership) {
                return response()->json([
                    'data' => [],
                    'message' => 'La membresia especificada no existe',
                    'success' => false
                ]);
            }

            $rules = [];
            $messages = [];
            $fieldsUpdate = [];
            if ($request->filled('user')) {
                $rules['user'] = 'exists:App\Models\User,id';
                $fieldsUpdate['user_id'] = $request->string('user')->trim();
                $messages['user.exists'] = 'El usuario especificado no existe';
            }

            if ($request->filled('start_date')) {
                $rules['start_date'] = Rule::date()->format('Y-m-d');
                $fieldsUpdate['start_date'] = $request->string('start_date')->trim();
                $messages['start_date.date_format'] = 'La fecha de inicio de la membresia no cumple con el formato (AAAA-MM-DD)';
            }

            if ($request->filled('end_date')) {
                $rules['end_date'] = [Rule::date()->format('Y-m-d'), 'after:start_date'];
                $fieldsUpdate['end_date'] = $request->string('end_date')->trim();
                $messages['end_date.after'] = 'La fecha de fin de la membresia debe ser mayor a la de inicio';
                $messages['end_date.date_format'] = 'La fecha de fin de la membresia no cumple con el formato (AAAA-MM-DD)';
            }

            if ($request->filled('pay') && in_array(strtolower($request->string('pay')->trim()), ['debe', 'pagado'])) {
                $rules['pay'] = Rule::in(['Debe', 'Pagado']);
                $fieldsUpdate['pay'] = $request->string('pay')->trim();
                $messages['pay.in'] = 'El estado del pago solo puede ser (Pagado, Debe)';
                if ($request->filled('pay') && strtolower($request->string('pay')->trim()) === "debe" && $request->filled('balance')) {
                    $rules['balance'] = 'integer';
                    $messages['balance.integer'] = 'El saldo solo deben ser números';
                    $fieldsUpdate['balance'] = $request->string('balance')->trim();
                }
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            $validator->after(function ($validator) use ($request) {
                if ($request->filled('pay') && strtolower($request->string('pay')->trim()) === "debe" && $request->isNotFilled('balance')) {
                    $validator->errors()->add('balance', 'Si el estado del pago es Debe, el saldo es requerido');
                }

                if ($request->filled('pay') && strtolower($request->string('pay')->trim()) === "pagado" && $request->filled('balance')) {
                    $validator->errors()->add('balance', 'Si el pago fue completado, no debe haber saldo');
                }
            });

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

            $membershipUpdate = $membership->update($fieldsUpdate);
            return response()->json([
                'data' => $membershipUpdate,
                'message' => 'Membresia actualizada con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al actualizar la membresia',
                'success' => false
            ]);
        }
    }

    /**
     * Elimina una membresia.
     */
    public function destroy(string $id)
    {
        try {
            if (!isset($id)) {
                return response()->json([
                    'data' => [],
                    'message' => 'Es necesario el identificador de la membresia',
                    'success' => false
                ]);
            }

            $membershipDelete = Membership::where('id', $id)->first();
            if ($membershipDelete) {
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible eliminar la membresia.',
                    'success' => false
                ]);
            }

            $membershipDelete->update(['state' => 'INACTIVE']);

            return response()->json([
                'data' => [],
                'message' => 'Membresia eliminada.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al eliminar la membresia',
                'success' => false
            ]);
        }
    }

    public function activityInformationFromHome() {
        try {
            $results = [];
            $quantityMembers = Member::where('state', 'ACTIVE')->count();
            $results['quantityMembers'] = $quantityMembers;

            $quantityMemberships = Membership::where('state', 'ACTIVE')->count();
            $results['quantityMemberships'] = $quantityMemberships;
            
            $now = date("Y-m-d");
            $currentDate = date("Y-m-d", strtotime($now . " +10 days"));
            $closeToExpiring = Membership::where('state', 'ACTIVE')->whereBetween('end_date', [$now, $currentDate])->count();
            $results['quntityCloseToExpiring'] = $closeToExpiring;

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
}
