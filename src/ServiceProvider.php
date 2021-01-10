<?php

namespace Bizarg\Test;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * Class ServiceProvider
 * @package Bizarg\test\src
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        if (env('DB_CONNECTION') == 'sqlite' && file_exists(DB::connection()->getDatabaseName())) {
            DB::getPdo()->sqliteCreateFunction('if', function ($expression, $true, $false) {
                $result = false;

                eval('$result = $expression ? true : false;');

                return $result ? $true : $false;
            }, 3);

            DB::getPdo()->sqliteCreateFunction('greatest', function ($date1, $date2) {
                return Carbon::parse($date1)->greaterThan(Carbon::parse($date2)) ? $date1 : $date2;
            }, 2);

            DB::getPdo()->sqliteCreateFunction('concat', function () {
                $args = func_get_args();
                $string = '';
                foreach ($args as $arg) {
                    $string .= $arg . '';
                }
                return $string;
            }, -1);

            DB::getPdo()->sqliteCreateFunction('date_format', function ($date, $format) {
                return Carbon::parse($date)->format(str_replace('%', '', $format));
            }, 2);

            DB::getPdo()->sqliteCreateFunction('now', function () {
                return Carbon::now()->format('Y-m-d h:i:s');
            });

            DB::getPdo()->sqliteCreateFunction('year', function ($date) {
                return Carbon::parse($date)->format('Y');
            }, 1);

            DB::getPdo()->sqliteCreateFunction('find_in_set', function ($string, $stringList) {
                if (is_null($string) || is_null($stringList)) {
                    return null;
                }

                if ($stringList === '') {
                    return 0;
                }

                $collect = collect(explode(',', $stringList));

                if ($collect->contains($string)) {
                    return $collect->search($string) + 1;
                }

                return 0;
            }, 2);

            DB::getPdo()->sqliteCreateFunction('datediff', function ($date1, $date2) {
                return Carbon::parse($date1)->diffInDays(Carbon::parse($date2));
            }, 2);

            DB::getPdo()->sqliteCreateFunction('format', function ($amount, $decimal) {
                return number_format($amount, $decimal, '.', ',');
            }, 2);

            DB::getPdo()->sqliteCreateFunction('lpad', function ($value, $length, $padString) {
                return str_pad($value, $length, $padString);
            }, 3);
        }
    }
}
