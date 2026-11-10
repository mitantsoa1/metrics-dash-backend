<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tampon d'ingestion (PU-51)
    |--------------------------------------------------------------------------
    | queue  : l'événement est d'abord écrit dans la file persistante (table jobs) puis la réponse
    |          202 part ; un worker (php artisan queue:work) l'enregistre, avec reprises à délai
    |          progressif. Un événement qui échoue toujours finit dans failed_jobs (dead-letter).
    | inline : écriture immédiate après l'envoi de la réponse, sans worker (pas de reprise, perdu si
    |          le processus s'arrête). À réserver au développement local sans worker.
    */
    'buffer' => env('INGESTION_BUFFER', 'queue'),

    // Une file en retard de plus de N secondes est signalée par ingestion:status
    'lag_alert_seconds' => (int) env('INGESTION_LAG_ALERT_SECONDS', 120),
];
