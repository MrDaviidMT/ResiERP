<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Política de conservación (RGPD art. 5.1.e): se revisa una vez al mes.
// Se estrena en modo simulación (config/conservacion.php): informa sin modificar nada.
Schedule::command('conservacion:aplicar')->monthlyOn(1, '03:00')->onOneServer();
