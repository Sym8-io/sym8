<!DOCTYPE html>
<html lang="en" dir="ltr" data-type="rewrite-tpl">
  <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title>Symphony Error</title>
    <link rel="stylesheet" href="{ASSETS_URL}/css/pico.min.css?v={VERSION}">
    <link rel="stylesheet" href="{ASSETS_URL}/css/pico-error.css?v={VERSION}">
    <link rel="stylesheet" href="{ASSETS_URL}/css/pico-messages.css?v={VERSION}">
    <script src="{ASSETS_URL}/js/symphony.min.js"></script>
    <script>Symphony.Context.add('root', '{URL}');Symphony.Context.add('env', {});</script>
  </head>
  <body id="error">
    <main class="container errorpage">
      <h1>Symphony</h1>
      <div class="frame">
        <div class="inner">
          <h2>Symphony Error</h2>
          <p class="message invalid">Module <code>mod_rewrite</code> is not enabled.</p>
          <p>It appears the <code>mod_rewrite</code> is not enabled or available on this server.</p>
        </div>
      </div>
    </main>
  </body>
</html>
