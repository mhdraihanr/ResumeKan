<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CvResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'title' => $this->title,
            'template' => $this->template,
            'language' => $this->language,
            'updated_at' => $this->updated_at,
        ];

        if ($request->routeIs('cvs.show') || $request->isMethod('post') || $request->isMethod('put')) {
            $base['data'] = $this->data;
            $base['created_at'] = $this->created_at;
        }

        return $base;
    }
}
