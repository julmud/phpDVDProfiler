<?php

/* Functions to support different currency formats in different locales */

function money_digits($CurrencyID) {
    switch ($CurrencyID) {
    case 'TND':
        return(3);

    case 'CLP':
    case 'KRW':
    case 'VEB':
        return(0);

    case 'ARP':
    case 'AUD':
    case 'BRL':
    case 'BSD':
    case 'CAD':
    case 'CHF':
    case 'CNY':
    case 'COP':
    case 'DKK':
    case 'EUR':
    case 'FJD':
    case 'GBP':
    case 'GHC':
    case 'HKD':
    case 'HNL':
    case 'IDR':
    case 'ILS':
    case 'INR':
    case 'ISK':
    case 'JPY':
    case 'LKR':
    case 'MAD':
    case 'MXP':
    case 'MYR':
    case 'NOK':
    case 'NZD':
    case 'PAB':
    case 'PEN':
    case 'PHP':
    case 'PKR':
    case 'RUR':
    case 'SEK':
    case 'SGD':
    case 'THB':
    case 'TRL':
    case 'TTD':
    case 'TWD':
    case 'USD':
    case 'ZAR':
    default:
        return(2);
    }
}

function money_prefix($CurrencyID) {
    switch ($CurrencyID) {
    case 'ARP':
    case 'AUD':
    case 'BRL':
    case 'BSD':
    case 'CAD':
    case 'CLP':
    case 'COP':
    case 'FJD':
    case 'HKD':
    case 'MXP':
    case 'NZD':
    case 'SGD':
    case 'TTD':
    case 'TWD':
    case 'USD':
        return('$');

    case 'CHF':
        return('Fr.');

    case 'CNY':
        return('Y');

    case 'DKK':
    case 'NOK':
        return('kr');

    case 'EUR':
        return('&euro;');

    case 'GBP':
    case 'TRL':
        return('&pound;');

    case 'GHC':
        return('&cent;');

    case 'HNL':
        return('L');

    case 'IDR':
        return('Rp.');

    case 'INR':
    case 'PKR':
        return('Rs.');

    case 'JPY':
        return('&yen;');

    case 'PEN':
        return('S.');

    case 'THB':
        return('B');

    case 'VEB':
        return('Bs.');

    case 'ZAR':
        return('R');

    case 'ILS':
    case 'ISK':
    case 'KRW':
    case 'LKR':
    case 'MAD':
    case 'MYR':
    case 'PAB':
    case 'PHP':
    case 'RUR':
    case 'SEK':
    case 'TND':
    default:
        return('');
    }
}

function money_postfix($CurrencyID) {
    switch ($CurrencyID) {
    case 'ISK':
        return(' kr');

    case 'SEK':
        return('kr');

    case 'ARP':
    case 'AUD':
    case 'BRL':
    case 'BSD':
    case 'CAD':
    case 'CHF':
    case 'CLP':
    case 'CNY':
    case 'COP':
    case 'DKK':
    case 'EUR':
    case 'FJD':
    case 'GBP':
    case 'GHC':
    case 'HKD':
    case 'HNL':
    case 'IDR':
    case 'ILS':
    case 'INR':
    case 'JPY':
    case 'KRW':
    case 'LKR':
    case 'MAD':
    case 'MXP':
    case 'MYR':
    case 'NOK':
    case 'NZD':
    case 'PAB':
    case 'PEN':
    case 'PHP':
    case 'PKR':
    case 'RUR':
    case 'SGD':
    case 'THB':
    case 'TND':
    case 'TRL':
    case 'TTD':
    case 'TWD':
    case 'USD':
    case 'VEB':
    case 'ZAR':
    default:
        return('');
    }
}

function my_money_format($CurrencyID, $value) {
global $lang;

    return(money_prefix($CurrencyID)
    . number_format($value, money_digits($CurrencyID), $lang['MON_DECIMAL_POINT'], $lang['MON_THOUSANDS_SEP'])
    . money_postfix($CurrencyID));
}
