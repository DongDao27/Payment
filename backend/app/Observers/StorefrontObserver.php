<?php

namespace App\Observers;

use App\Services\StorefrontSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StorefrontObserver
{
    public function saved(Model $model): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => StorefrontSnapshot::invalidate());
        } else {
            StorefrontSnapshot::invalidate();
        }
    }

    public function deleted(Model $model): void
    {
        $this->saved($model);
    }
}
