<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'domain' => $this->domain,
            'status' => $this->status,
            'package' => new PackageResource($this->whenLoaded('package')),
            'disk_usage' => $this->disk_usage,
            'disk_limit' => $this->disk_limit,
            'bandwidth_usage' => $this->bandwidth_usage,
            'bandwidth_limit' => $this->bandwidth_limit,
            'max_ftp_accounts' => $this->max_ftp_accounts,
            'max_email_accounts' => $this->max_email_accounts,
            'max_databases' => $this->max_databases,
            'max_subdomains' => $this->max_subdomains,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => [
                'self' => route('api.v1.accounts.show', $this->id),
                'package' => route('api.v1.packages.show', $this->package_id),
            ],
        ];
    }
} 