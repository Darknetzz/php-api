<?php

/**
 * Example endpoints: random quote and fun fact.
 *
 * Data lives in examples/quotes.php and examples/funfacts.php (tracked in git).
 */

function api_quote(): array
{
    $quotes = require __DIR__ . '/quotes.php';

    $rollAuthor = mt_rand(0, count($quotes) - 1);
    $authorName = $quotes[$rollAuthor][0];
    $authorQuotes = $quotes[$rollAuthor][1];
    $rollQuote = mt_rand(0, count($authorQuotes) - 1);
    $quote = trim($authorQuotes[$rollQuote]);

    return ['quote' => [$authorName, $quote]];
}

function api_funfact(): array
{
    $funfacts = require __DIR__ . '/funfacts.php';
    $roll = mt_rand(0, count($funfacts) - 1);

    return [
        'funfact' => $funfacts[$roll]['fact'],
        'extraInfo' => $funfacts[$roll]['extraInfo'],
    ];
}
