<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sender = $this->user;
        $name = trim(
            ($sender->userInfo?->first_name ?? '') . ' ' . ($sender->userInfo?->last_name ?? '')
        ) ?: ($sender->role === 'admin' ? 'Admin' : $sender->email);

        return [
            'id' => (string) $this->id,
            'from' => $sender->role === 'admin' ? 'admin' : 'client',
            'name' => $name,
            'body' => $this->body,
            'time' => $this->created_at->timezone('Asia/Manila')->format('M j, g:i A'),
            'created_at' => $this->created_at->toIso8601String(),
            'attachment_url' => $this->attachment_url,
            'attachment_name' => $this->attachment_path ? basename($this->attachment_path) : null,
        ];
    }
}
