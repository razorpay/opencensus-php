<?php

/**
 * Amount is in paise here
 * @param  float $amount Amount in paise
 * @return string Formatted money value with 2 decimals and commas
 */
Blade::extend(function($view, $compiler)
{
    $pattern = $compiler->createMatcher('format_money');
    setlocale(LC_MONETARY, 'en_IN');
    return preg_replace($pattern, "$1<?php echo money_format('%!i', ($2/100)); ?>", $view);
});
