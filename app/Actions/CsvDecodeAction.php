<?php

namespace App\Actions;

use Generator;

class CsvDecodeAction
{

  public function handle($file, $delimiter)
  {
    if (($handle = fopen($file, "r")) === false) {
      die("can't open the file.");
    }

    $csv_headers = fgetcsv($handle, 4000, $delimiter);
    $csv_headers = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv_headers);
    $data = array();

    while ($row = fgetcsv($handle, 9000, $delimiter)) {
      // Convert each field from ISO-8859-1/Windows-1252 to UTF-8 and sanitize
      $row = array_map(function ($field) {
        if ($field === null) {
          return $field;
        }
        // Detect and convert encoding to UTF-8
        $encoding = mb_detect_encoding($field, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
          $field = mb_convert_encoding($field, 'UTF-8', $encoding);
        }
        // Remove any remaining invalid UTF-8 characters
        $field = mb_convert_encoding($field, 'UTF-8', 'UTF-8');
        return $field;
      }, $row);

      $data[] = array_combine($csv_headers, $row);
    }

    fclose($handle);
    return $data;
  }

  /**
   * Stream CSV rows one at a time using a generator to avoid memory issues
   */
  public function stream($file, $delimiter): Generator
  {
    if (($handle = fopen($file, "r")) === false) {
      throw new \RuntimeException("Can't open the file: $file");
    }

    $csv_headers = fgetcsv($handle, 4000, $delimiter);
    $csv_headers = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv_headers);

    while ($row = fgetcsv($handle, 9000, $delimiter)) {
      $row = array_map(function ($field) {
        if ($field === null) {
          return $field;
        }
        $encoding = mb_detect_encoding($field, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
          $field = mb_convert_encoding($field, 'UTF-8', $encoding);
        }
        $field = mb_convert_encoding($field, 'UTF-8', 'UTF-8');
        return $field;
      }, $row);

      yield array_combine($csv_headers, $row);
    }

    fclose($handle);
  }

  /**
   * Count rows in CSV without loading into memory
   */
  public function countRows($file): int
  {
    if (($handle = fopen($file, "r")) === false) {
      throw new \RuntimeException("Can't open the file: $file");
    }

    $count = -1; // Start at -1 to exclude header row
    while (fgets($handle) !== false) {
      $count++;
    }

    fclose($handle);
    return max(0, $count);
  }
}
