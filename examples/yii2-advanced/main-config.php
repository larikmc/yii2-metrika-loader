<?php

/**
 * Example Yii2 Advanced Configuration: frontend/config/main.php or common/config/main.php
 */

return [
    'components' => [
        // Universal Yandex Metrika Loader component
        'metrika' => [
            'class' => \larikmc\metrika\MetrikaLoader::class,
            'counterId' => 113667541, // Replace with your counter ID
            'mode' => \larikmc\metrika\MetrikaLoader::MODE_DEFERRED, // 'deferred' for high PSI / low TBT, 'immediate' for standard snippet
            'enabled' => !YII_DEBUG || YII_ENV_PROD, // Or true/false
            'maxDelay' => 3500, // Safety fallback delay in ms
            'idleTimeout' => 1500, // requestIdleCallback timeout
            'loadOnInteraction' => true, // Immediate load on scroll/click/touch
            'webvisor' => true,
            'clickmap' => true,
            'trackLinks' => true,
            'accurateTrackBounce' => true,
            'ecommerce' => 'dataLayer',
            'params' => [
                'ssr' => true,
            ],
            // Optional: Cookie consent integration
            'requireConsent' => false,
            'consentStorageKey' => '64web_cookie_consent_v1',
            'consentEvent' => 'cookie_analytics_enabled',
            'rejectEvent' => 'cookie_analytics_disabled',
            // Optional: restrict to specific production hostnames
            'allowedHosts' => [
                '64web.ru',
                'www.64web.ru',
            ],
        ],
    ],
];
