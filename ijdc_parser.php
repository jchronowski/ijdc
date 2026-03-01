<?php

// ============================================================================
// IJDC Parser - Simple, Clean
// ============================================================================


// ============================================================================
// Parse IJDC File
// Read and store structure as-is
// ============================================================================

function parseIJDC($filePath) {
    if (!file_exists($filePath)) {
        return ['error' => 'File not found'];
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    $data = [];
    $inBlock = false;
    $blockKey = '';
    $blockContent = '';

    foreach ($lines as $index => $line) {
        $line = rtrim($line);

        // Skip version on first line
        if ($index === 0 && strpos($line, 'version:ijdc') === 0) {
            $data['_version'] = trim(substr($line, 12));
            continue;
        }

        // Skip empty lines
        if (empty($line)) {
            continue;
        }

        // Opening a block
        if (strpos($line, '<<^') !== false && !$inBlock) {
            $colonPos = strpos($line, ':');
            $blockKey = substr($line, 0, $colonPos);

            // Block closes on same line
            if (strpos($line, '^>>') !== false) {
                $start = strpos($line, '<<^') + 3;
                $end = strpos($line, '^>>');
                $blockContent = substr($line, $start, $end - $start);

                if (substr($blockKey, 0, 1) !== '-') {
                    if (isset($data[$blockKey]) && !is_array($data[$blockKey])) {
                        $data[$blockKey] = [$data[$blockKey]];
                    }
                    if (isset($data[$blockKey])) {
                        $data[$blockKey][] = $blockContent;
                    } else {
                        $data[$blockKey] = $blockContent;
                    }
                }
            } else {
                // Block continues
                $inBlock = true;
                $start = strpos($line, '<<^') + 3;
                $blockContent = substr($line, $start);
            }
            continue;
        }

        // Closing a block
        if ($inBlock && strpos($line, '^>>') !== false) {
            $end = strpos($line, '^>>');
            $blockContent .= "\n" . substr($line, 0, $end);
            $inBlock = false;

            if (substr($blockKey, 0, 1) !== '-') {
                if (isset($data[$blockKey]) && !is_array($data[$blockKey])) {
                    $data[$blockKey] = [$data[$blockKey]];
                }
                if (isset($data[$blockKey])) {
                    $data[$blockKey][] = trim($blockContent);
                } else {
                    $data[$blockKey] = trim($blockContent);
                }
            }
            continue;
        }

        // Inside a block
        if ($inBlock) {
            $blockContent .= "\n" . $line;
            continue;
        }

        // Scalar field
        if (strpos($line, ':') !== false) {
            $colonPos = strpos($line, ':');
            $key = substr($line, 0, $colonPos);
            $value = substr($line, $colonPos + 1);

            if (substr($key, 0, 1) !== '-') {
                if (isset($data[$key]) && !is_array($data[$key])) {
                    $data[$key] = [$data[$key]];
                }
                if (isset($data[$key])) {
                    $data[$key][] = $value;
                } else {
                    $data[$key] = $value;
                }
            }
        }
    }

    return $data;
}


// ============================================================================
// Process Content Field
// Convert (^( to <pre> and )^) to </pre>
// Convert code tags back to normal
// ============================================================================

function processContent($content) {
    $content = str_replace('(^(', '<pre>', $content);
    $content = str_replace(')^)', '</pre>', $content);
    $content = str_replace('|code|||~php~', '&lt;?php', $content);
    $content = str_replace('~php~|||code|', '?&gt;', $content);
    $content = str_replace('|code|||~!DOCTYPE html~', '&lt;!DOCTYPE html&gt;', $content);
    $content = str_replace('|code|||~html~', '&lt;html&gt;', $content);
    $content = str_replace('~/html~|||code|', '&lt;/html&gt;', $content);
    $content = str_replace('|code|||~head~', '&lt;head&gt;', $content);
    $content = str_replace('~/head~|||code|', '&lt;/head&gt;', $content);
    $content = str_replace('|code|||~body~', '&lt;body&gt;', $content);
    $content = str_replace('~/body~|||code|', '&lt;/body&gt;', $content);
    $content = str_replace('|code|||~', '&lt;', $content);
    $content = str_replace('~|||code|', '&gt;', $content);
    return $content;
}

$ijdcFile = __DIR__ . '/sample2/recipe_chat.ijdc';
$ijdcData = parseIJDC($ijdcFile);

if (isset($ijdcData['error'])) {
    echo 'Error: ' . htmlspecialchars($ijdcData['error']);
    return;
}

?>
<hr>
	<?php
		$before = " • ";       						// html before the link
		$after = " • ";             							// html after the link
		$show_submenu = 0; 
		//$thisparent	= 52;	// show the sub menu   1        or not  0
     // record id numbers the   AREA(id) 
		include ('./securecabinet/site_nav.bullet');
?>
<hr>
<?php foreach ($ijdcData as $key => $value): ?>

<?php if ($key === '_version' || $key === 'title' || $key === 'conversation_id' || $key === 'created_at' || $key === 'updated_at' || $key === 'conversation_mode' || $key === 'model') continue; ?>

<div>
    <strong><?php echo htmlspecialchars($key); ?>:</strong>

    <?php if (is_array($value)): ?>

        <?php foreach ($value as $item): ?>

            <div style="margin-left: 20px; margin-top: 10px;">
                <?php
                // Check if item is piped sub-fields (has | and key:value pattern)
                if (strpos($item, '|') !== false && strpos($item, ':') !== false && strpos($item, '|code|||') === false) {
                    // Parse sub-fields
                    $subFields = explode('|', $item);
                    foreach ($subFields as $subField) {
                        $subField = trim($subField);
                        if (strpos($subField, ':') !== false) {
                            $colonPos = strpos($subField, ':');
                            $subKey = trim(substr($subField, 0, $colonPos));
                            $subValue = trim(substr($subField, $colonPos + 1));
                            
                            echo '<div style="margin-bottom: 10px;">';
                            echo '<strong>' . htmlspecialchars($subKey) . ':</strong> ';
                            
                            // Check if subValue has code blocks
                            if (strpos($subValue, '(^(') !== false || strpos($subValue, '|code|||~') !== false) {
                                echo processContent($subValue);
                            } else {
                                echo htmlspecialchars($subValue);
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    // Large content block - check for code or pre
                    if (strpos($item, '(^(') !== false || strpos($item, '|code|||~') !== false) {
                        echo processContent($item);
                    } else {
                        echo htmlspecialchars($item);
                    }
                }
                ?>
            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div style="margin-left: 20px;">
            <?php echo htmlspecialchars($value); ?>
        </div>

    <?php endif; ?>

</div>

<div style="margin-top: 15px;"></div>

<?php endforeach; ?>