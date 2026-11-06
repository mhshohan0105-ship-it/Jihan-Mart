<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Newsletter signups from the storefront footer (email and/or phone). */
#[Fillable(['email', 'phone'])]
class Subscriber extends Model {}
