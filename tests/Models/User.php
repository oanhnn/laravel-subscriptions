<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Subscriptions\Models\Concerns\Subscribable;
use Laravel\Subscriptions\Models\Contracts\Subscriber;

class User extends Model
{
    use Subscribable;
}
