<?php

return [
    'generator_url' => env('CODE_GENERATOR_URL') ?: rtrim(env('APP_URL', 'http://localhost'), '/').'/codes/access',
];
