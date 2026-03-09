<?php

//$uid = 100000003;

//$file = './trregisteredvisitors/' . $uid . '.ijdc';

$code_start = '(^(';
$code_end   = ')^)';


/* ------------------------------------------------------------
   turn code-marked content into displayable <pre> content
------------------------------------------------------------ */
function process_code_block($content) {
    global $code_start, $code_end;

    if (str_starts_with($content, $code_start) && str_ends_with($content, $code_end)) {
        $content = substr($content, strlen($code_start), -strlen($code_end));
    }

    $content = str_replace(
        ['&lt;', '&gt;'],
        ['<', '>'],
        $content
    );

    $replacements = [
        '|code|||~php~'           => '<?php',
        '~php~|||code|'           => '?>',
        '|code|||~!DOCTYPE html~' => '<!DOCTYPE html>',
        '|code|||~html~'          => '<html>',
        '~/html~|||code|'         => '</html>',
        '|code|||~head~'          => '<head>',
        '~/head~|||code|'         => '</head>',
        '|code|||~body~'          => '<body>',
        '~/body~|||code|'         => '</body>',
        '|code|||~'               => '<',
        '~|||code|'               => '>',
    ];

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);

    return '<pre>' . $content . '</pre>';
}


/* ------------------------------------------------------------
   split subfields on | BUT ignore | while inside (^( ... )^)
------------------------------------------------------------ */
function split_subfields($text) {
    global $code_start, $code_end;

    $parts = [];
    $current = '';
    $in_code = false;

    $len = strlen($text);

    for ($i = 0; $i < $len; $i++) {
        if (!$in_code && substr($text, $i, strlen($code_start)) === $code_start) {
            $in_code = true;
            $current .= $code_start;
            $i += strlen($code_start) - 1;
            continue;
        }

        if ($in_code && substr($text, $i, strlen($code_end)) === $code_end) {
            $in_code = false;
            $current .= $code_end;
            $i += strlen($code_end) - 1;
            continue;
        }

        if (!$in_code && $text[$i] === '|') {
            $parts[] = $current;
            $current = '';
            continue;
        }

        $current .= $text[$i];
    }

    if ($current !== '') {
        $parts[] = $current;
    }

    return $parts;
}


/* ------------------------------------------------------------
   parse any field like:
   book:<<^title:The Red Tent|content:(^(stuff here)^)^>>
------------------------------------------------------------ */
function parse_block_field($fieldname, $file) {
    global $code_start;

    if (!file_exists($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') continue;
        if (strpos($line, '-') === 0) continue;
        if (strpos($line, $fieldname . ':') !== 0) continue;

        $value = trim(substr($line, strlen($fieldname) + 1));

        if (str_starts_with($value, '<<^') && str_ends_with($value, '^>>')) {
            $value = substr($value, 3, -3);
        }

        $parts = split_subfields($value);
        $out = [];

        foreach ($parts as $part) {
            if (strpos($part, ':') === false) continue;

            [$subkey, $subvalue] = explode(':', $part, 2);

            $subkey = trim($subkey);
            $subvalue = trim($subvalue);

            if (str_starts_with($subvalue, $code_start)) {
                $subvalue = process_code_block($subvalue);
            }

            $out[$subkey] = $subvalue;
        }

        return $out;
    }

    return [];
}


/* ------------------------------------------------------------
   get just one subfield
------------------------------------------------------------ */
function get_block_subfield($fieldname, $subfieldname, $file) {
    $arr = parse_block_field($fieldname, $file);
    return $arr[$subfieldname] ?? '';
}


/* ------------------------------------------------------------
   CALL IT
------------------------------------------------------------ */

$book = parse_block_field('book', $file);

echo 'title: ' . ($book['title'] ?? '') . '<br>';
echo 'content: ' . ($book['content'] ?? '') . '<br><br>';

echo 'title only: ' . get_block_subfield('book', 'title', $file) . '<br>';
echo 'content only: ' . get_block_subfield('book', 'content', $file) . '<br>';
?>