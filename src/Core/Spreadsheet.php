<?php

declare(strict_types=1);

namespace App\Core;

use ZipArchive;

/**
 * Зависимостей не требует: читает XLSX (OpenXML: ZIP + XML) и CSV.
 * Возвращает таблицу в виде списка строк; каждая строка — список ячеек,
 * выровненных по индексу колонки (0 = A).
 */
final class Spreadsheet
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Читает файл и возвращает таблицу:
     *   headers — первая строка, если она похожа на шапку (артикул/наименование/цена …);
     *   rows    — строки данных.
     *
     * @return array{headers: array<int,string>, rows: array<int,array<int,string>>}
     */
    public static function read(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Файл не найден.');
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $rows = $ext === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
        $rows = array_values($rows);

        if (!$rows) {
            return ['headers' => [], 'rows' => []];
        }

        if (self::looksLikeHeader($rows[0])) {
            return [
                'headers' => array_map(static fn ($v): string => trim((string) $v), $rows[0]),
                'rows'    => array_slice($rows, 1),
            ];
        }

        $headers = [];
        for ($i = 0, $n = count($rows[0]); $i < $n; $i++) {
            $headers[] = self::colName($i);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ---------------- XLSX ----------------

    private static function readXlsx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Для импорта XLSX требуется расширение PHP zip (ZipArchive).');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Не удалось открыть файл XLSX: архив повреждён или не является книгой Excel.');
        }

        $strings = self::xlsxSharedStrings($zip);
        $sheetFile = self::xlsxFirstSheet($zip);
        $xmlRaw = $zip->getFromName($sheetFile);
        $zip->close();

        if ($xmlRaw === false) {
            throw new \RuntimeException('В книге не найдено ни одного рабочего листа.');
        }

        $xml = @simplexml_load_string($xmlRaw);
        if ($xml === false) {
            throw new \RuntimeException('Не удалось разобрать рабочий лист книги (повреждённый XML).');
        }

        $rows = [];
        foreach ($xml->children(self::MAIN_NS)->sheetData->children(self::MAIN_NS)->row as $row) {
            $cells = [];
            $maxCol = -1;
            foreach ($row->children(self::MAIN_NS)->c as $cell) {
                $attrs = $cell->attributes();
                $col = self::xlsxColIndex((string) $attrs['r']);
                $cells[$col] = self::xlsxCellValue($cell, $attrs, $strings);
                if ($col > $maxCol) {
                    $maxCol = $col;
                }
            }
            if ($maxCol < 0) {
                continue;
            }
            $line = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private static function xlsxSharedStrings(ZipArchive $zip): array
    {
        $raw = $zip->getFromName('xl/sharedStrings.xml');
        if ($raw === false) {
            return [];
        }
        $xml = @simplexml_load_string($raw);
        if ($xml === false) {
            return [];
        }
        $strings = [];
        foreach ($xml->children(self::MAIN_NS)->si as $si) {
            $strings[] = self::readText($si);
        }
        return $strings;
    }

    /**
     * Текстовое содержимое узла вместе со всеми вложенными <t> (в т.ч. rich-text <r><t>).
     */
    private static function readText(\SimpleXMLElement $node): string
    {
        $text = '';
        $children = $node->children(self::MAIN_NS);
        if (count($children) === 0) {
            return (string) $node;
        }
        foreach ($children as $child) {
            $text .= self::readText($child);
        }
        return $text;
    }

    private static function xlsxFirstSheet(ZipArchive $zip): string
    {
        $sheet = '';
        $relsRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsRaw !== false) {
            $relsNs = 'http://schemas.openxmlformats.org/package/2006/relationships';
            $rels = @simplexml_load_string($relsRaw);
            if ($rels !== false) {
                foreach ($rels->children($relsNs)->Relationship as $rel) {
                    if (str_contains((string) $rel['Type'], '/worksheet')) {
                        $sheet = (string) $rel['Target'];
                        break;
                    }
                }
            }
        }
        if ($sheet !== '' && !str_starts_with($sheet, 'xl/')) {
            $sheet = 'xl/' . ltrim($sheet, '/');
        }
        return $sheet !== '' ? $sheet : 'xl/worksheets/sheet1.xml';
    }

    private static function xlsxCellValue(\SimpleXMLElement $cell, \SimpleXMLElement $attrs, array $strings): string
    {
        $children = $cell->children(self::MAIN_NS);

        switch ((string) $attrs['t']) {
            case 's':
                $idx = (int) $children->v;
                return trim((string) ($strings[$idx] ?? ''));

            case 'inlineStr':
                $is = $children->is;
                return $is !== null ? trim(self::readText($is)) : '';

            default:
                $v = $children->v;
                return $v !== null ? trim((string) $v) : '';
        }
    }

    private static function xlsxColIndex(string $ref): int
    {
        $letters = (string) preg_replace('/[^A-Za-z]/', '', $ref);
        $index = 0;
        foreach (str_split($letters) as $ch) {
            $base = ctype_upper($ch) ? ord('A') : ord('a');
            $index = $index * 26 + (ord($ch) - $base + 1);
        }
        return max(0, $index - 1);
    }

    // ---------------- CSV ----------------

    private static function readCsv(string $path): array
    {
        $raw = (string) @file_get_contents($path);
        if ($raw === '') {
            return [];
        }

        // BOM и перекодировка в UTF-8
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        } elseif (str_starts_with($raw, "\xFF\xFE")) {
            $raw = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($raw, "\xFE\xFF")) {
            $raw = mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
        } elseif (!mb_check_encoding($raw, 'UTF-8')) {
            $enc = mb_detect_encoding($raw, ['Windows-1251', 'CP866', 'ISO-8859-1'], true);
            $converted = @mb_convert_encoding($raw, 'UTF-8', $enc !== false ? $enc : 'Windows-1251');
            if (is_string($converted) && $converted !== '') {
                $raw = $converted;
            }
        }

        $delim = self::detectDelimiter($raw);

        $rows = [];
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return $rows;
        }
        fwrite($fh, $raw);
        rewind($fh);

        while (($row = fgetcsv($fh, 0, $delim, '"', "\\")) !== false) {
            $rows[] = array_map(static fn ($v): string => trim((string) $v), $row);
        }
        fclose($fh);

        while ($rows !== [] && implode('', end($rows)) === '') {
            array_pop($rows);
        }

        return $rows;
    }

    private static function detectDelimiter(string $raw): string
    {
        $pos = strcspn($raw, "\r\n");
        $head = substr($raw, 0, $pos);
        if ($head === '') {
            return ';';
        }
        $counts = [
            ';' => substr_count($head, ';'),
            ',' => substr_count($head, ','),
            "\t" => substr_count($head, "\t"),
            '|' => substr_count($head, '|'),
        ];
        arsort($counts);
        $best = key($counts);
        return $best !== null && $counts[$best] > 0 ? $best : ';';
    }

    // ---------------- Common ----------------

    private static function looksLikeHeader(array $row): bool
    {
        foreach ($row as $cell) {
            $h = mb_strtolower(trim((string) $cell), 'UTF-8');
            if (preg_match(
                '/артикул|наименован|назван|бренд|производител|наличие|кол[- ]?во|остаток|цена|стоимост|sku|brand|price|qty|stock|usd/u',
                $h
            )) {
                return true;
            }
        }
        return false;
    }

    private static function colName(int $index): string
    {
        $name = '';
        $index++;
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }
}