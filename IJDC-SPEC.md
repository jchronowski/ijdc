# IJDC — Inflection Journal Data Container

**Version:** 1.2  
**Author:** Juanita Chronowski — http://www.jack-in.com  
**Effective:** February 28, 2026  
**File extension:** `.ijdc`

A portable, human-readable, language-agnostic flat-file data container format.  
Delimiter-based. No dependencies. Possible replacement for `.json`.

---

## Why IJDC?

- Human-readable without a parser
- Works in any language — PHP, Python, JS, Go, whatever you write
- Handles scalars, arrays, structured sub-fields, and freeform text in one format
- Safe code storage built in
- Designed for data portability, e-signature systems, and AI passport records
- No empty fields — if it doesn't exist, it isn't in the file

---

## File Structure

Line 1 is always the version tag. Every line after that is a field.

```
version:ijdc 1.2
nickname:PinkBird
phone:313-333-3333
social_media:twitter/jchronowski
social_media:github.com/jchronowski
permissions:<<^read|write|comment^>>
address:<<^street:123 Main St|city:Detroit|state:MI|country:USA|postal:48211^>>
about_me:<<^I build things at the intersection of AI and accountability.
This field can span multiple lines.
HTML is fine here too.^>>
```

---

## Field Types

### Scalar — no delimiters
Short single values. Clean and readable.
```
nickname:PinkBird
phone:313-333-3333
country:USA
```

### Repeated key lines
One line per entry. The parser collects all values for that key as an array.
```
social_media:twitter/jchronowski
social_media:facebook.com/juanitac
book:The Design of Everyday Things
book:Thinking Fast and Slow
```

### Flat array — pipe-delimited
Multiple values in one block. Split on pipe `|`.
```
permissions:<<^read|write|comment^>>
```

### Structured sub-fields
One field with multiple named parts. Think of it like an XML element — all parts belong together.  
Sub-fields are pipe-separated `key:value` pairs inside one block.
```
address:<<^street:123 Main St|apt:3|city:Detroit|state:MI|country:USA|postal:48211^>>
book:<<^isbn:384645274628478|author:S. King|title:Bluebeard^>>
```
> No empty sub-fields. If a value is absent, omit that sub-field entirely.

### Freeform / multiline text block
Prose, HTML, or any text. Multi-line is allowed.  
No leading or trailing spaces on the line immediately after `<<^` or immediately before `^>>`.
```
about_me:<<^I am a researcher and builder.
This is line two.
HTML is fine here too.^>>
```

---

## Delimiter Rules

| Delimiter | Meaning |
|-----------|---------|
| `<<^` | Opens a block field |
| `^>>` | Closes a block field |
| `\|` | Separates array values or sub-fields within a block |
| `:` | Separates key from value (split on first colon only) |

**Critical:**
- No space after `<<^` — ever
- No space before `^>>` — ever
- No blank lines inside a block
- No leading/trailing spaces on lines inside a block — stripped on submit
- `<<^` and `^>>` on the same line = inline block
- `<<^` without closing on same line = block mode — read until `^>>` closes it

---

## Invalid / Removed Fields — Dash Prefix

Fields are not deleted. A dash prefix marks the old value as invalid.  
The new value is added on the very next line. Similar fields stay grouped.

```
-phone:313-333-3333
phone:313-555-5555
```

Both lines remain. Consuming code uses the most recent non-dashed entry as the active value.

---

## Forbidden Patterns

These four patterns are hard-rejected from all user input.  
They are reserved as system delimiters and code-wrapper tokens.

```
<<^
^>>
|code|||~
~|||code|
```

> `{{[` and `]}}` are **not** forbidden. They are valid token markers used inside block fields for form/e-signature records.

---

## Code Sanitization

Executable tags are translated to safe storage on write and restored on read.

| Original | Stored As |
|----------|-----------|
| `<?php` | `\|code\|\|\|~php~` |
| `?>` | `~php~\|\|\|code\|` |
| `<script` | `\|code\|\|\|~script~` |
| `</script>` | `~script~\|\|\|code\|` |
| `<style` | `\|code\|\|\|~css~` |
| `</style>` | `~css~\|\|\|code\|` |
| `<html` | `\|code\|\|\|~html~` |
| `</html>` | `~html~\|\|\|code\|` |

Python blocks: consecutive lines with 4-space indentation are wrapped with `|code|||~py~` ... `~py~|||code|` at write time by the consuming code.

---

## Parsing

Read one line at a time:

1. Line 1 — version tag. Informational only. Continue regardless.
2. Every new line is a new field. Split on **first colon only** → key and value.
3. No `<<^` → scalar. Store immediately.
4. `<<^` and `^>>` on same line → inline block. Extract content between them.
5. `<<^` without closing on same line → block mode. Accumulate lines until `^>>` closes it.
6. Key starts with `-` → invalid/removed marker. Store as-is.
7. Same key appears multiple times → collect all values as array.
8. Inspect first 50 chars of block: `|` with `text:text` pattern → array. No pattern → freeform.
9. Apply code sanitization on all output values.
10. Never write empty fields. Absent values are omitted.

### PHP example — structured block
```php
$raw    = /* content extracted from between <<^ and ^>> */;
$fields = explode('|', $raw);
foreach ($fields as $field) {
    [$k, $v] = explode(':', $field, 2);
}
```

---

## Form / E-Signature Token Syntax

Tokens live inside `<<^ ^>>` blocks. Used in form master and filled form records.

Syntax: `{{[type:fieldname]}}` or `{{[type:fieldname;default_value]}}`

| Token Type | Input | Notes |
|------------|-------|-------|
| `txt` | Text input (single-line) | |
| `num` | Number input | Integer or decimal |
| `eml` | Email field | Valid format enforced |
| `tel` | Telephone input | |
| `dt` | Date picker | YYYY-MM-DD. Use `now()` for today. |
| `tm` | Time picker | HH:MM or HH:MM:SS |
| `ck` | Checkbox | Single yes/no |
| `ckm` | Checkbox multiple | Multiple select |
| `rd` | Radio buttons | Choose one |
| `sel` | Select dropdown | Single choice |
| `ta` | Textarea | Multi-line text |
| `sig` | Signature capture | Digital signature |
| `ini` | Initials | Usually 2–4 chars |
| `cur` | Currency | Money value with symbol |
| `yn` | Yes/No toggle | Binary choice |

**Master form** — token is the placeholder:
```
name_field:<<^{{[txt:artist]}}^>>
```
Renders as: `<input type="text" name="artist" value="">`

**Filled form** — token stores the submitted value:
```
name_field:<<^{{[txt:artist;Juanita Chronowski]}}^>>
```
Renders as: `<input type="text" name="artist" value="Juanita Chronowski">`

---

## Serial Format

Used to uniquely identify records.

```
Raw:    [owner_id][MMDDYYYY][HHMM]
Public: Base37 encoded (A-Z, 0-9, _) — human-readable, URL-safe
```

The encoded serial becomes the filename. The filename IS the identifier.

---

## License

This specification is open. Implement it in any language.  
If you improve it, share it back. That's what GitHub is for.

© 2026 Juanita Chronowski — http://www.jack-in.com
