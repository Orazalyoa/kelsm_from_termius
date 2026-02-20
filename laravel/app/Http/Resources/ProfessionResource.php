<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $locale = $request->get('locale', 'ru');
        
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->getName($locale),
            'name_ru' => $this->name_ru,
            'name_kk' => $this->name_kk,
            'name_en' => $this->name_en,
            'name_zh' => $this->name_zh,
            'description' => $this->description,
            'is_for_expert' => $this->is_for_expert,
            'is_for_lawyer' => $this->is_for_lawyer,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
