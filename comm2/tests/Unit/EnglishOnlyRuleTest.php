<?php

declare(strict_types=1);

use App\Rules\EnglishOnly;
use Illuminate\Support\Facades\Validator;

test('english text passes validation', function () {
    $rule = new EnglishOnly();

    $validator = Validator::make([
        'comment' => 'This is a perfectly valid English sentence with clarity.',
    ], [
        'comment' => [$rule],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('non ascii content fails validation', function () {
    $rule = new EnglishOnly();

    $validator = Validator::make([
        'comment' => 'Este reporte incluye caracteres ñ and á',
    ], [
        'comment' => [$rule],
    ]);

    expect($validator->fails())->toBeTrue();
});

test('text without letters fails english rule', function () {
    $rule = new EnglishOnly();

    $validator = Validator::make([
        'comment' => '1234567890!@#$%',
    ], [
        'comment' => [$rule],
    ]);

    expect($validator->fails())->toBeTrue();
});
