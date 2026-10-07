<?php

use App\Services\SearchQueryNormalizer;

test('it normalizes processor and colloquial generation queries', function () {
    $queries = SearchQueryNormalizer::buildSearchQueries('core i5 13chi pakalenya');

    expect($queries)->toContain('Core I5 13')
        ->toContain('I5 13-pokoleniya')
        ->toContain('I5 13th');
});

test('it normalizes uzbek avlod queries with laptop models', function () {
    $queries = SearchQueryNormalizer::buildSearchQueries('hp victus i5 13-avlod rtx 3050');

    expect($queries)->toContain('HP Victus Core I5 13')
        ->toContain('Core I5 13');
});

test('it strips sales noise and stop words from descriptions', function () {
    $queries = SearchQueryNormalizer::buildSearchQueries('iphone 15 pro max 256gb yengi karobka bor Toshkent sotiladi');

    expect($queries[0])->toBe('iphone 15 pro max 256gb');
});

test('it builds clean tavily query for laptops and phones', function () {
    $laptopQuery = SearchQueryNormalizer::buildTavilyQuery('core i5 13chi pakalenya');
    expect($laptopQuery)->toContain('Core I5 13')
        ->toContain('noutbuk');

    $phoneQuery = SearchQueryNormalizer::buildTavilyQuery('samsung s23 ultra 512gb ideal holatda');
    expect($phoneQuery)->toContain('samsung s23 ultra 512gb')
        ->toContain('sotiladi');
});
