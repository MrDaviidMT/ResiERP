<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Política de conservación de datos (RGPD art. 5.1.e)
    |--------------------------------------------------------------------------
    |
    | Plazos tras los que un dato personal deja de ser necesario. Se han tomado
    | los más largos de la normativa aplicable:
    |
    | - Historia clínica: 15 años desde el alta del proceso asistencial. El mínimo
    |   estatal (Ley 41/2002 art. 17) son 5 años; hay normas autonómicas que llegan a 15.
    | - Registro de jornada: 4 años (Estatuto de los Trabajadores art. 34.9).
    | - Auditoría y cuentas de empleados: 6 años, por encima de los plazos de
    |   prescripción de las infracciones que podrían investigarse (RGPD y LOPDGDD
    |   hasta 3 años; art. 197 del Código Penal, unos 5).
    |
    */

    'anios' => [
        'historia_clinica' => (int) env('CONSERVACION_HISTORIA_CLINICA', 15),
        'registro_jornada' => (int) env('CONSERVACION_REGISTRO_JORNADA', 4),
        'auditoria' => (int) env('CONSERVACION_AUDITORIA', 6),
        'cuentas_empleados' => (int) env('CONSERVACION_CUENTAS_EMPLEADOS', 6),
        'familiares_sin_vinculo' => (int) env('CONSERVACION_FAMILIARES', 1),
    ],

    /*
    | En modo simulación el comando no modifica nada: solo informa de lo que haría.
    | Se estrena así a propósito; para aplicarlo de verdad, CONSERVACION_SIMULACION=false.
    */
    'simulacion' => (bool) env('CONSERVACION_SIMULACION', true),

];
