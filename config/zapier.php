<?php

// Whenever the ownership of the Zap account is transferred, the URL of the webhook changes , so need to change url if account ownership changes

return [
    'signups'     => 'https://hooks.zapier.com/hooks/catch/6118268/2e1xtg/',
    'submissions' => 'https://hooks.zapier.com/hooks/catch/1088429/46x8fa/',
    'activations' => 'https://hooks.zapier.com/hooks/catch/1088429/4twqyo/',
    'mock'        => env('ZAPIER_MOCK', false),
];
