IJDC 1.4 is a lightweight plain-text container format for structured records, repeated values, rich text blocks, and safely stored code snippets. It is designed to stay readable to humans, easy to parse in scripts, and flexible enough for profiles, notes, form records, conversation logs, and display-driven content.

Each .ijdc file is data only. It should live in a protected storage folder and be read by scripts, not served directly to the public. The frontend should request only the fields it needs, then render those fields in the layout and style you want.

Version 1.4 keeps the line-based clarity of earlier IJDC releases while tightening up parser guidance, display rules, repeated-field handling, structured subfields, and block retrieval patterns. It also documents practical helper patterns for PHP and Python so humans and AIs can both work with the format without guessing.

If you want one sentence for the homepage: IJDC is a display-first data container—human-readable in storage, script-readable in code, and meant to reveal only the exact pieces of information you choose to render.