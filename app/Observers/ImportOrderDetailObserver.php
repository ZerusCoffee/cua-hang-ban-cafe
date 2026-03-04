<?php

namespace App\Observers;

use App\Models\ImportOrderDetail;

class ImportOrderDetailObserver
{
    /**
     * Handle the ImportOrderDetail "created" event.
     */
    public function created(ImportOrderDetail $importOrderDetail): void
    {
        //
    }

    /**
     * Handle the ImportOrderDetail "updated" event.
     */
    public function updated(ImportOrderDetail $importOrderDetail): void
    {
        //
    }

    /**
     * Handle the ImportOrderDetail "deleted" event.
     */
    public function deleted(ImportOrderDetail $importOrderDetail): void
    {
        //
    }

    /**
     * Handle the ImportOrderDetail "restored" event.
     */
    public function restored(ImportOrderDetail $importOrderDetail): void
    {
        //
    }

    /**
     * Handle the ImportOrderDetail "force deleted" event.
     */
    public function forceDeleted(ImportOrderDetail $importOrderDetail): void
    {
        //
    }
}
