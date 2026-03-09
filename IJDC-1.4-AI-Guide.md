# IJDC 1.4 - AI Quick Guide

## What IJDC is
IJDC stands for **Inflection Journal Data Container**. It is a plain-text, line-based storage format for records that need to stay readable, script-friendly, and easy to display selectively.

Use it for:
- user profiles
- notes
- repeated links or tags
- structured objects like addresses
- HTML/text blocks
- safely stored code samples
- display-driven forms and logs

## Core rule
Each `.ijdc` file is **data only**.
Do **not** expose the folder for direct public clicking.
Store `.ijdc` files in a protected folder that is readable by server-side scripts, then render only the fields needed on the frontend.

## First line
```txt
version:ijdc 1.4
```
This is informational. The parser should not reject a file solely because the version tag differs.

## Field types
### 1. Scalar field
One line, one value.
```txt
nickname:Unicorn
user_id:100000003
record_type:user_profile
```

### 2. Repeated field
Same key on multiple lines. Consumer collects them as an array.
```txt
soc_link:www.jack-in.com
soc_link:facebook.com/juanitac
soc_link:twitter.com/jchronowski47
```

### 3. Block field
Use `<<^` and `^>>` for structured, multiline, HTML, or complex content.
```txt
call_transcribed:<<^some stuff here <img src='logo.png'> even html^>>
```

### 4. Structured block with named subfields
Pipe-delimited `key:value` pairs inside one block.
```txt
address:<<^number:123 street|city:Detroit|state:MI|zip:48211^>>
```

### 5. Mixed content block with code wrapper
```txt
book:<<^title:The Red Tent|content:(^(The books description here and maybe some code &lt;strong&gt;bold&lt;/strong&gt;)^)^>>
```

## Invalid or historical values
Prefix old values with `-` instead of deleting them.
```txt
-phone:313-333-3333
phone:313-555-5555
```
Consumers should ignore dashed lines for active output.

## Parsing rules
1. Read file line by line.
2. Split on the **first colon only**.
3. If the value starts with `<<^`, treat it as a block.
4. If `^>>` is on the same line, it is an inline block.
5. If not, keep reading until the closing marker appears.
6. Preserve block content exactly unless your app intentionally normalizes it.
7. If the same key appears multiple times, collect values into an array.
8. If a key starts with `-`, treat it as disabled or historical.

## Code sanitization
Store executable tags in a safe translated form and restore them only when intentionally rendering code.

Example mappings:
```txt
<?php        -> |code|||~php~
?>           -> ~php~|||code|
<html>       -> |code|||~html~
</html>      -> ~/html~|||code|
```

## Security rules
- `.ijdc` folders should not be public browse/download folders.
- They should be readable by backend scripts only.
- Frontend pages should request only the field(s) they need.
- Never dump whole records if you only need one field.
- Treat raw HTML blocks as trusted content only if the source is trusted.

## Display philosophy
IJDC is **not** meant to be dumped raw to the browser.
It is meant to be interpreted and displayed selectively.

Good:
- read `nickname`
- show `address.city`
- render one HTML block intentionally
- collect all `soc_link` values into a list

Bad:
- link directly to the `.ijdc` file
- expose protected storage folders
- output raw trusted blocks without deciding whether they should render as HTML or text

## Example PHP patterns
### Get one scalar
```php
function show_one_field($fieldname) {
    global $ijdc_path;
    if (!file_exists($ijdc_path)) return '';
    $lines = file($ijdc_path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '-') === 0) continue;
        if (strpos($line, $fieldname . ':') === 0) {
            return trim(substr($line, strlen($fieldname) + 1));
        }
    }
    return '';
}
```

### Get one raw block
```php
function get_block_field($fieldname) {
    global $ijdc_path;
    if (!file_exists($ijdc_path)) return '';
    $lines = file($ijdc_path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '-') === 0) continue;
        if (strpos($line, $fieldname . ':') !== 0) continue;
        $value = trim(substr($line, strlen($fieldname) + 1));
        if (str_starts_with($value, '<<^') && str_ends_with($value, '^>>')) {
            return substr($value, 3, -3);
        }
        return $value;
    }
    return '';
}
```

### Get repeated fields as an array
```php
function get_all_fields($fieldname) {
    global $ijdc_path;
    $results = [];
    if (!file_exists($ijdc_path)) return $results;
    $lines = file($ijdc_path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '-') === 0) continue;
        if (strpos($line, $fieldname . ':') === 0) {
            $results[] = trim(substr($line, strlen($fieldname) + 1));
        }
    }
    return $results;
}
```

## Example Python parser behavior
- keep a current `in_block` flag
- collect block lines until `^>>`
- append repeated keys as arrays
- split on first `:` only
- restore safe code markers only when needed

## Tiny sample record
```txt
version:ijdc 1.4
record_type:user_profile
user_id:100000003
nickname:Unicorn
soc_link:www.jack-in.com
soc_link:facebook.com/juanitac
call_transcribed:<<^some stuff here <img width="20" src='logo.png'> even html^>>
address:<<^number:123 street|city:Detroit|state:MI|zip:48211^>>
book:<<^title:The Red Tent|content:(^(The books description here and maybe some code &lt;strong&gt;bold&lt;/strong&gt;)^)^>>
```

## Short takeaway for AI tools
When reading IJDC:
- treat it as structured text, not markup to expose directly
- split on first colon only
- preserve block content carefully
- collect repeated keys
- ignore dashed historical keys for active display
- keep protected storage protected
- render only what the page actually needs
