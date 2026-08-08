<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'domain', 'redirect_path'])]
class PwaClient extends Model
{
    public function callbackUrl(): string
    {
        return 'https://'.$this->domain.$this->redirect_path;
    }
}
