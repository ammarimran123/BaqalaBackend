<?php

namespace App\Listeners;

use App\Events\importUpdateStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class sendUpdatedProgress
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  importUpdateStatus  $event
     * @return void
     */
    public function handle(importUpdateStatus $event)
    {
        Storage::put('listenerOut.txt', $event);
        // setcookie("importBarPercentage", ceil($event), "/products");
    }
}
