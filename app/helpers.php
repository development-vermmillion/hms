<?php

if (! function_exists('money')) {
    /** Format an amount with the logged-in hotel's currency symbol. */
    function money($value): string
    {
        $symbol = auth()->user()?->hotel?->currency_symbol ?? '₹';

        return $symbol.' '.number_format((float) $value, 2);
    }
}
