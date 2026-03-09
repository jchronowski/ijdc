<?php





/*
|--------------------------------------------------------------------------
| get_all_fields($fieldname)
|--------------------------------------------------------------------------
| Reads the file and returns ALL active values for that field as an array.
|
| Example file lines:
|   soc_link:www.jack-in.com
|   soc_link:facebook.com/juanitac
|   soc_link:twitter.com/jchronowski47
|   -soc_link:oldlink.com
|
| Returns:
|   [
|     "www.jack-in.com",
|     "facebook.com/juanitac",
|     "twitter.com/jchronowski47"
|   ]
|--------------------------------------------------------------------------
*/
function get_all_fields($fieldname) {
    global $ijdc_path;

    $results = [];

    if (!file_exists($ijdc_path)) {
        return $results;
    }

    $lines = file($ijdc_path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // skip blank lines
        if ($line === '') continue;

        // skip disabled lines like -soc_link:whatever
        if (strpos($line, '-') === 0) continue;

        // match only lines starting with fieldname:
        if (strpos($line, $fieldname . ':') === 0) {
            // take everything after the colon
            $results[] = trim(substr($line, strlen($fieldname) + 1));
        }
    }

    return $results;
}


/*
|--------------------------------------------------------------------------
| get_field_count($fieldname)
|--------------------------------------------------------------------------
| Returns how many active records exist for that field.
|
| Example:
|   echo get_field_count('soc_link');
|--------------------------------------------------------------------------
*/
function get_field_count($fieldname) {
    $all = get_all_fields($fieldname);
    return count($all);
}


/*
|--------------------------------------------------------------------------
| get_field_by_number($fieldname, $number)
|--------------------------------------------------------------------------
| Returns one item by human-friendly position:
|   1 = first
|   2 = second
|   3 = third
|   15 = fifteenth
|
| Returns empty string if that numbered item does not exist.
|--------------------------------------------------------------------------
*/
function get_field_by_number($fieldname, $number) {
    $all = get_all_fields($fieldname);

    // convert human numbering to array index
    $index = $number - 1;

    return $all[$index] ?? '';
}


/*
|--------------------------------------------------------------------------
| get_field_containing($fieldname, $contains)
|--------------------------------------------------------------------------
| Returns the first matching value that contains a word like
| twitter, facebook, jack-in, etc.
|--------------------------------------------------------------------------
*/
function get_field_containing($fieldname, $contains) {
    $all = get_all_fields($fieldname);

    foreach ($all as $value) {
        if (stripos($value, $contains) !== false) {
            return $value;
        }
    }

    return '';
}


/*
|--------------------------------------------------------------------------
| EXAMPLES
|--------------------------------------------------------------------------
*/

// get all social links as an array
$thisfieldhere = get_all_fields('soc_link');

// show how many there are
echo "<strong>Total social links:</strong> " . get_field_count('soc_link') . "<br><br>";


// show them all dynamically
echo "<strong>All social links:</strong><br>";
foreach ($thisfieldhere as $i => $link) {
    echo ($i + 1) . ". " . htmlspecialchars($link) . "<br>";
}

echo "<br>";


// show one at a time by number
echo "<strong>By number:</strong><br>";
echo "1st: " . htmlspecialchars(get_field_by_number('soc_link', 1)) . "<br>";
echo "2nd: " . htmlspecialchars(get_field_by_number('soc_link', 2)) . "<br>";
echo "3rd: " . htmlspecialchars(get_field_by_number('soc_link', 3)) . "<br>";
echo "15th: " . htmlspecialchars(get_field_by_number('soc_link', 15)) . "<br>";

echo "<br>";


// show one by search word
echo "<strong>By word match:</strong><br>";
echo "Twitter: " . htmlspecialchars(get_field_containing('soc_link', 'twitter')) . "<br>";
echo "Facebook: " . htmlspecialchars(get_field_containing('soc_link', 'facebook')) . "<br>";
echo "Jack-in: " . htmlspecialchars(get_field_containing('soc_link', 'jack-in')) . "<br>";

?>