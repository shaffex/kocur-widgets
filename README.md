## Working small widget for editing in app

```
<body>
  <intentbutton action="playSystemSound:1033" buttonStyle="plain" onAppear="setInt:kState=1\\setString:kocurEmojis={{EMOJIS}}\\setBool:kocurIsNewVideo={{IS_NEWVIDEO}}\\setString:kocurStatus={{STATUS}}">
    <vstack spacing="4">
      <text font="largeTitle">$kocurEmojis</text>
      <text font="title" bold="true">Kocur</text>

      <text if="js:$kState==1" font="body" xlineLimit="2" foregroundColor="green" minimumScaleFactor="0.5" multilineTextAlignment="center">$kocurStatus</text>
      <text if="js:$kState==2">MIW</text>

      <!-- if kocurNewVideo is true -->
      <text if="js:$kocurIsNewVideo" font="caption" padding="4" background="red" cornerRadius="8">NEW VIDEO!</text>
    </vstack>
  </intentbutton>
</body>
```



Old lukes small widget

```
<body>
  <intentbutton action="playSystemSound:1030" buttonStyle="plain">
  <vstack padding="2">
    <text font="largeTitle">🐈‍⬛</text>
    <text font="title" foregroundColor="blue">Kocur</text>
    <text font="caption" foregroundColor="secondary">🐈‍⬛se zlosci</text>
      <text padding="1" background="green" cornerRadius="8">NEW zlostiace VIDEO</text>
  </vstack>
    </intentbutton>
</body>
```
