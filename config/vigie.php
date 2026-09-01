<?php

return [
    'digest' => [
        // En dessous de ce score, un item est considéré comme du bruit et
        // n'est jamais inclus dans un digest.
        'min_relevance_score' => env('VIGIE_DIGEST_MIN_RELEVANCE_SCORE', 50),
    ],
];
