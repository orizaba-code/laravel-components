<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;


if ( ! function_exists('untrailingSlashit')) {
    function untrailingSlashIt(string $string)
    {
        return rtrim($string, '/\\');
    }
}


if ( ! function_exists('trailingSlashit')) {
    function trailingSlashIt(string $string)
    {
        return untrailingSlashIt($string) . '/';
    }
}


if ( ! function_exists('isLocalEnvironment')) {
    function isLocalEnvironment()
    {
        return (in_array(env('APP_ENV', ''), ['local', 'debug']) || boolval(env('APP_DEBUG', false)));
    }
}


if ( ! function_exists('logOnLocal')) {
    function logOnLocal(string $message, array $data = [], ?\Illuminate\Console\Command $command = null)
    {
        if (isLocalEnvironment()) {
            $backtrace      = null;
            $titlePrefix    = ' ******* INFO: ' . PHP_EOL;
            $backtraceLimit = env('APP_DEBUG_BACKTRACE_LIMIT', 2);

            if ($backtraceLimit > 0) {
                $backtrace = collect(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $backtraceLimit) ?? []);

                foreach ($backtrace as $backtraceEntry) {
                    $titlePrefix .= '  >>>>>>> ';

                    if (isset($backtraceEntry['file']) && isset($backtraceEntry['line'])) {
                        $titlePrefix .= $backtraceEntry['file'] . ':' . $backtraceEntry['line'];
                    }

                    if (isset($backtraceEntry['function'])) {
                        $titlePrefix .= ' => ' . $backtraceEntry['function'] . PHP_EOL;
                    }
                }
            }

            $postFix = empty($data) ? '' : ' ******* DATA: ';

            \Illuminate\Support\Facades\Log::info($titlePrefix . PHP_EOL . '<<<<<<<<< ' . $message . PHP_EOL . $postFix, $data);

            if (isset($command)) {
                $command->info($titlePrefix . PHP_EOL . '<<<<<<<<< ' . $message . PHP_EOL . $postFix . json_encode($data));
            }
        }
    }
}


if ( ! function_exists('getArraySubset')) {
    /**
     * Returns the given <keys> in <array>. Returns a formatted string,
     * a single value, or an array with the values for the keys.
     * If any of the <keys> is empty and $ignoreEmpty == true
     * that record will be filtered from the results
     *
     * @param array $array
     * @param string|array $keys
     * @param string $format
     * @param bool $ignoreEmpty
     *
     * @return array
     */
    function getArraySubset(array $array, string|array $keys, string $format = "", bool $ignoreEmpty = true): array
    {
        $parsedElements = array_map(function ($row) use ($keys, $format) {

            // If any of the keys is empty return null
            $values = [];
            foreach ((is_array($keys) ? $keys : [$keys]) as $k) {
                if (empty($row[$k])) {
                    return null;
                }

                $values[] = $row[$k];
            }

            return empty($format) ? (is_array($keys) ? $values : $values[0]) : vsprintf($format, $values);
        }, $array);

        // Removes all nulls with array_filter
        return $ignoreEmpty ? array_filter($parsedElements) : $parsedElements;
    }
}


if ( ! function_exists('readCsvToKeyPairArray')) {
    /**
     * @param $filePath
     *
     * @return array|null
     */
    function readCsvToKeyPairArray($filePath)
    {
        $data    = [];
        // Open CSV File
        if ( ! ($fp = fopen($filePath, 'r'))) {
            return null;
        }

        $headers = fgetcsv($fp, "1024", ",");
        $parsedHeaders = array_map(fn($v) => preg_replace('/[^A-Za-z0-9\-\_]/', '', \Illuminate\Support\Str::snake($v)) ,$headers);

        while ($row = fgetcsv($fp, "1024", ",")) {
            $parsedRow = array_map(fn($v) => is_numeric($v) ? floatval($v) : $v ,$row);
            $data[] = array_combine($parsedHeaders, $parsedRow);
        }

        // Close CSV File
        fclose($fp);

        return $data;
    }
}


if ( ! function_exists('readCsvToCollection')) {
    function readCsvToCollection($filePath)
    {
        $keyPairs = readCsvToKeyPairArray($filePath);

        return isset($keyPairs) ? new Collection($keyPairs) : $keyPairs;
    }
}