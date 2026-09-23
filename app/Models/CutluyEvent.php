<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $cutluy_payment_id
 * @property string $event
 */
#[Fillable(['cutluy_payment_id', 'event'])]
class CutluyEvent extends Model {}
