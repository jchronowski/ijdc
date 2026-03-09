<?php




/*
|--------------------------------------------------------------------------
| get_single_field_array($fieldname)
|--------------------------------------------------------------------------
| Finds one field like:
|
|   address:<<^number:123 street|city:Detroit|state:MI|zip:48211^>>
|
| or:
|
|   home:<<^number:123 street|city:Detroit|state:MI|zip:48211^>>
|
| It returns an associative array like:
|
|   [
|       'number' => '123 street',
|       'city'   => 'Detroit',
|       'state'  => 'MI',
|       'zip'    => '48211'
|   ]
|
| It skips disabled lines like:
|   -address:<<^...^>>
|--------------------------------------------------------------------------
*/
function get_single_field_array($fieldname) {
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

        // skip disabled lines
        if (strpos($line, '-') === 0) continue;

        // must start with fieldname:
        if (strpos($line, $fieldname . ':') !== 0) continue;

        // get everything after fieldname:
        $value = trim(substr($line, strlen($fieldname) + 1));

        // remove <<^ and ^>> if present
        if (str_starts_with($value, '<<^') && str_ends_with($value, '^>>')) {
            $value = substr($value, 3, -3);
        }

        // split into parts by |
        $parts = explode('|', $value);

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') continue;
            if (strpos($part, ':') === false) continue;

            [$subkey, $subvalue] = explode(':', $part, 2);

            $subkey   = trim($subkey);
            $subvalue = trim($subvalue);

            $results[$subkey] = $subvalue;
        }

        // stop after first matching field
        return $results;
    }

    return $results;
}


/*
|--------------------------------------------------------------------------
| get_single_subfield($fieldname, $subfieldname)
|--------------------------------------------------------------------------
| Example:
|   echo get_single_subfield('address', 'city');
|   echo get_single_subfield('home', 'zip');
|--------------------------------------------------------------------------
*/
function get_single_subfield($fieldname, $subfieldname) {
    $field_array = get_single_field_array($fieldname);
    return $field_array[$subfieldname] ?? '';
}


/*
|--------------------------------------------------------------------------
| EXAMPLE CALLS
|--------------------------------------------------------------------------
*/

// get whole address/home array dynamically
$address_parts = get_single_field_array('address');

// show all subfield names and values dynamically
echo "<strong>All address parts:</strong><br>";
foreach ($address_parts as $key => $value) {
    echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "<br>";
}

echo "<br>";


// call one subfield at a time
echo "<strong>Individual parts:</strong><br>";
echo "number: " . htmlspecialchars(get_single_subfield('address', 'number')) . "<br>";
echo "city: " . htmlspecialchars(get_single_subfield('address', 'city')) . "<br>";
echo "state: " . htmlspecialchars(get_single_subfield('address', 'state')) . "<br>";
echo "zip: " . htmlspecialchars(get_single_subfield('address', 'zip')) . "<br>";

?>