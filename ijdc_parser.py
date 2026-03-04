from pathlib import Path
from typing import Dict, Any, List, Union
import re

# --------->>>>>>>  THIS PARSER WORKS JDC March 03 2026 16:33
def parse_ijdc(file_path: str | Path) -> Dict[str, Any]:
    file_path = Path(file_path)
    if not file_path.is_file():
        return {"error": "File not found"}

    data: Dict[str, Any] = {}
    in_block = False
    block_key = ""
    block_content_lines: List[str] = []

    with file_path.open("r", encoding="utf-8") as f:
        lines = f.readlines()

    for line in lines:
        line_raw = line.rstrip("\r\n")

        if in_block:
            if "^>>" in line_raw:
                end = line_raw.find("^>>")
                block_content_lines.append(line_raw[:end])
                full_content = "".join(block_content_lines)
                _append(data, block_key, full_content)
                in_block = False
                block_content_lines = []
            else:
                block_content_lines.append(line)
            continue

        colon_pos = line_raw.find(":")
        if colon_pos == -1:
            continue

        key = line_raw[:colon_pos].rstrip()
        value_start = line_raw[colon_pos + 1:]

        if value_start.lstrip().startswith("<<^"):
            block_key = key
            start = value_start.find("<<^") + 3
            if "^>>" in value_start:
                end = value_start.find("^>>", start)
                content = value_start[start:end]
                _append(data, key, content)
            else:
                in_block = True
                block_content_lines = [value_start[start:] + "\n"]
        else:
            _append(data, key, value_start)

    return data


def _append(data: Dict[str, Any], key: str, value: Any) -> None:
    if key in data:
        if not isinstance(data[key], list):
            data[key] = [data[key]]
        data[key].append(value)
    else:
        data[key] = value


def process_code_block(content: str) -> str:
    content = content.removeprefix("(^(").removesuffix(")^)")

    content = content.replace("&lt;", "<").replace("&gt;", ">").replace("&quot;", '"')

    replacements = [
        ('|code|||~php~', '<?php'),
        ('~php~|||code|', '?>'),
        ('|code|||~!DOCTYPE html~', '<!DOCTYPE html>'),
        ('|code|||~html~', '<html>'),
        ('~/html~|||code|', '</html>'),
        ('|code|||~head~', '<head>'),
        ('~/head~|||code|', '</head>'),
        ('|code|||~body~', '<body>'),
        ('~/body~|||code|', '</body>'),
        ('|code|||~', '<'),
        ('~|||code|', '>'),
    ]
    for old, new in replacements:
        content = content.replace(old, new)

    return content


def render_message_block(item: str) -> None:
    # Find the prefix up to content:
    # We split only the part before the content block
    content_start = item.find("content:")
    if content_start == -1:
        print(item.strip())
        return

    prefix = item[:content_start].strip()
    content_part = item[content_start + 8:].strip()  # after "content:"

    # Split prefix on | (role and timestamp)
    prefix_parts = [p.strip() for p in prefix.split("|") if p.strip()]

    role = ""
    timestamp = ""
    for part in prefix_parts:
        if ":" not in part:
            continue
        k, v = [x.strip() for x in part.split(":", 1)]
        if k == "role":
            role = v
        elif k == "timestamp":
            timestamp = v

    print(f"role: {role}")
    print(f"timestamp: {timestamp}")
    print("content: ", end="")

    # Now process the full content part
    if content_part.startswith("(^("):
        print()  # newline before code block
        print(process_code_block(content_part))
    else:
        print(content_part)


def render_item(item: str) -> None:
    stripped = item.strip()

    if "role:" in stripped and "timestamp:" in stripped and "content:" in stripped:
        render_message_block(item)
    else:
        if stripped.lstrip().startswith("(^("):
            print(process_code_block(item))
        else:
            print(item.rstrip("\r\n"))


if __name__ == "__main__":
    script_dir = Path(__file__).parent.resolve()
    ijd_file = script_dir / "recipe_chat.ijdc"

    parsed = parse_ijdc(ijd_file)

    if "error" in parsed:
        print("Error:", parsed["error"])
        print("Current dir:", script_dir)
    else:
        #print("=== Parsed IJDC ===")
        print()

        skip = {"version","dev_comment", "title", "conversation_id", "created_at", "updated_at", "conversation_mode", "model"}

        for key, val in parsed.items():
            if key in skip:
                continue

            print(f"{key}:")

            if isinstance(val, list):
                for item in val:
                    render_item(item)
                    print()
            else:
                render_item(val)

            #print("-" * 60)
            print()