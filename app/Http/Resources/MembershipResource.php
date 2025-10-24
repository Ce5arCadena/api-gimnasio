<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'pay' => $this->pay,
            'balance' => $this->balance,
            'state' => $this->state,
            'user' => new MemberResource($this->whenLoaded('member')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
