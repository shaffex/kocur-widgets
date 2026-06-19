# Widget Update Instructions

Use this guide when asking Codex to update a remote widget XML and upload it to the server.

## What To Ask

Tell Codex three things:

1. The widget owner: `petres` or `lukes`
2. The widget size: `systemSmall`, `systemMedium`, or `systemLarge`
3. What content should be shown

Example:

```text
Update Petres' medium widget with today's WC26 matches. Keep the current two-column layout and upload it.
```

Another example:

```text
Update Lukes' small widget to show "Kocur is happy" with a cat emoji and upload it.
```

## Valid Widget Targets

Users:

```text
petres
lukes
```

Families:

```text
systemSmall
systemMedium
systemLarge
```

Server URLs:

```text
https://magic-ui.com/KumWidgets/petres.php?family=systemSmall
https://magic-ui.com/KumWidgets/petres.php?family=systemMedium
https://magic-ui.com/KumWidgets/petres.php?family=systemLarge
https://magic-ui.com/KumWidgets/lukes.php?family=systemSmall
https://magic-ui.com/KumWidgets/lukes.php?family=systemMedium
https://magic-ui.com/KumWidgets/lukes.php?family=systemLarge
```

## Local Files

Widget XML files live here:

```text
Server/KumWidgets/data/{user}_{family}.xml
```

Examples:

```text
Server/KumWidgets/data/petres_systemMedium.xml
Server/KumWidgets/data/lukes_systemSmall.xml
```

## Upload API

Preferred API call:

```json
{
  "action": "upload_xml",
  "user": "petres",
  "family": "systemMedium",
  "xml": "<body>...</body>"
}
```

Compatibility API call:

```json
{
  "action": "save",
  "user": "petres",
  "family": "systemMedium",
  "content": "<body>...</body>"
}
```

Endpoint:

```text
https://magic-ui.com/KumWidgets/api.php
```

## Codex Workflow

When asked to update a widget, Codex should:

1. Read the current local XML from `Server/KumWidgets/data/{user}_{family}.xml`
2. Preserve the existing layout unless asked to redesign it
3. Update only the requested content
4. Save the local XML file
5. POST the XML to `https://magic-ui.com/KumWidgets/api.php`
6. Fetch the served widget URL to verify the server returns the new XML

## Example Requests

```text
Read Petres' medium widget and update the matches to the latest WC26 fixtures. Keep the 2-column layout and upload it.
```

```text
Update Petres' medium widget with the latest WC26 scores. If matches are not finished yet, show kickoff times instead. Upload and verify.
```

```text
Update Lukes' small widget with this XML and upload it:

<body>
  <vstack>
    <text font="largeTitle">Cat</text>
    <text font="caption">Kocur status updated</text>
  </vstack>
</body>
```

```text
Make Petres' large widget show today's World Cup fixtures grouped by time. Keep it compact and upload it.
```

```text
Change only the title in Petres' medium widget to "WC26 Today" and upload it.
```

## Notes

- For live or current sports data, Codex should check the web before editing.
- If the sandbox blocks network access, Codex should request permission to run `curl` with network access.
- If the server has not yet deployed the `upload_xml` action, use the existing `save` action.
- Always verify the final served URL after uploading.
