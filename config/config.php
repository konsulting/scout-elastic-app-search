<?php

return [
    /** See documentation for app-search-php https://github.com/elastic/app-search-php **/
    'endpoint' => env('SCOUT_ELASTIC_APP_SEARCH_ENDPOINT', 'http://localhost:3002'),
    'api-key' => env('SCOUT_ELASTIC_APP_SEARCH_API_KEY', ''),
];
