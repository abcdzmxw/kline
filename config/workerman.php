<?php

return [

    'swap' => [
        'service' => \App\Workerman\Swap\Swap::class,
        'eventHandler' => \App\Workerman\Swap\Events::class,
    ],

    'option' => [
        'service' => \App\Workerman\Option\Option::class,
        'eventHandler' => \App\Workerman\Option\Events::class,
    ],

];
