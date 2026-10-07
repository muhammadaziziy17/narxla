<?php

use App\Services\DeviceParser;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->parser = new DeviceParser;
});

test('it matches the longest model name', function () {
    $parsed = $this->parser->parse('iPhone 15 Pro Max, 256 GB, ideal holat');

    expect($parsed)->not->toBeNull()
        ->and($parsed['brand'])->toBe('Apple')
        ->and($parsed['model'])->toBe('iPhone 15 Pro Max')
        ->and($parsed['storage'])->toBe(256)
        ->and($parsed['condition'])->toBe('ideal');
});

test('it extracts storage in different formats', function () {
    expect($this->parser->extractStorage('iPhone 15, 1 TB'))->toBe(1024)
        ->and($this->parser->extractStorage('iPhone 15, 512 gb'))->toBe(512)
        ->and($this->parser->extractStorage('Samsung S24, 8/256'))->toBe(256)
        ->and($this->parser->extractStorage('iPhone 15'))->toBe(128);
});

test('it extracts battery percentage accurately', function () {
    expect($this->parser->extractBattery('iPhone 15, batareya 85%'))->toBe(85)
        ->and($this->parser->extractBattery('iPhone 14, akb 79'))->toBe(79)
        ->and($this->parser->extractBattery('iPhone 15'))->toBe(90);
});

test('it extracts device condition accurately', function () {
    expect($this->parser->extractCondition('iPhone 15 yangi, ideal'))->toBe('ideal')
        ->and($this->parser->extractCondition('iPhone 14 ekran tirnalgan'))->toBe('scratched')
        ->and($this->parser->extractCondition('iPhone 15 oddiy holatda'))->toBe('good');
});

test('it sanitizes storage values safely', function () {
    expect($this->parser->sanitizeStorage(256))->toBe(256)
        ->and($this->parser->sanitizeStorage(999))->toBe(128);
});

test('it sanitizes battery bounds safely', function () {
    expect($this->parser->sanitizeBattery(85))->toBe(85)
        ->and($this->parser->sanitizeBattery(150))->toBe(90)
        ->and($this->parser->sanitizeBattery(-5))->toBe(90);
});
