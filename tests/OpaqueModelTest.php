<?php

declare(strict_types=1);

use AiSdk\Generate;
use AiSdk\Groq;

afterEach(function () {
    Generate::reset();
    Groq::reset();
});

it('uses adapter capabilities for opaque Groq model ids', function () {
    Groq::create(['apiKey' => 'gsk-test']);
    $model = Groq::model('vendor/private-model');

    expect($model->modelId())->toBe('vendor/private-model');
});
