<?php
header('Content-Type: text/xml; charset=utf-8');
$family = $_GET['family'] ?? 'systemSmall';

$widgets = [
    'systemSmall' => '
<body xcontainerBackgroundForWidget="yellow">
  <intentbutton action="playSystemSound:1109" buttonStyle="plain">
    <vstack padding="16">
    <text font="largeTitle">🐈‍⬛🐈‍⬛</text>
    <text font="title" foregroundColor="random" bold="true">Kocur</text>
    <text font="caption">robi update</text>
    <!--
    <button2 font="caption" action="https://ciernykocursmart.github.io/ciernykocursmart/">KOCUR 🎮</button2>
    -->
  </vstack>
  </intentbutton>
</body>',
    'systemMedium' => '<body xcontainerBackgroundForWidget="clear">
    <zstack>
    
  <vstack alignment="leading" maxWidth="infinity">
    <hstack>
    <text foregroundColor="white" maxWidth="infinity" padding="2" background="orange" cornerRadius="16" bold="">🐈‍⬛🗞️ KOCUR NEWS 🗞️🐈‍⬛</text>
    </hstack>
    <spacer/>
    
    <!--
    <text maxWidth="infinity" italic="true" xfont="custom:Courier;size:14" shadow="color:red;radius:10;x:5;y:5">🐈‍⬛ uz ma zapas a dava X 🐣</text>
<button2 maxWidth="infinity" action="https://www.flashscore.sk/zapas/futbal/leeds-tUxUbLR2/west-ham-Cxq57r8g/?mid=EgMYT5mm">Click to reveal</button2>button2>
-->
      
      
      
    
      
    
      
    <text font="body" xforegroundColor="secondary">🐈‍⬛ Uz kuka na zapasy, nech da potom druhy kocur znac ked bude mat fajny KI tip</text>
    
      
    
      <spacer/>
    
      
    <!--
    <text font="footnote" xmaxWidth="infinity" foregroundColor="white" tracking="3" padding="4" background="red" cornerRadius="16" opacity="0.90" rotationEffect="0"> Kocurovi sa pacili videa </text>
-->      
<!--
    <text font="headline" foregroundColor="secondary">KI bets:</text>
    <text font="footnote" foregroundColor="secondary" strikethrough="true">⚽️ Verona - Fiorentina: failed</text>
    
-->
  </vstack>
        </zstack>
</body>',
];

if (isset($widgets[$family])) {
    echo $widgets[$family];
} else {
    http_response_code(404);
    echo '<error>Unknown family: ' . htmlspecialchars($family) . '</error>';
}
