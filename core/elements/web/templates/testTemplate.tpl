<!doctype html>
<html lang="en">
<head>
    <title>[[*pagetitle]] - [[++site_name]]</title>
    <base href="[[!++site_url]]" />
    <meta charset="[[++modx_charset]]" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
   {set $ctx = $_modx->resource.context_key}

</head>
<body>

<div class="container">
    <section>
        <h1>[[*longtitle:default=`[[*pagetitle]]`]]</h1>
        [[*content]]
    </section>
</div>
<footer class="disclaimer">
    <p>{include "file:$ctx/chunks/footer.tpl"}</p>
</footer>

</body>
</html>
