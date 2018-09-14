<!doctype html>
<html lang="en">
<head>
    <title>[[*pagetitle]] - [[++site_name]]</title>
    <base href="[[!++site_url]]" />
    <meta charset="[[++modx_charset]]" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <!-- bootstrap -->
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- /bootstrap -->
    {$_modx->runSnippet('!ajax_call')}
    {$_modx->runSnippet('!prepareFilter')}
    {set $ctx = $_modx->resource.context_key}
    {set $company = $_modx->user.company_id}
    {set $user = $_modx->user.id}

    {if $ctx == 'dev'}
        <script src="[[++assets_url]]components/themebootstrap/js/jquery.min.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/timeline.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/timeline-locales.js"></script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src="[[++assets_url]]components/themebootstrap/js/bootstrap.min.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/moment-with-locales.min.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/bootstrap-datetimepicker.min.js"></script>

        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/add.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]][[*context_key]]/css/bootstrap-datetimepicker.min.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]][[*context_key]]/css/timeline.css?v=20180618">
    {/if}
    {if $ctx == 'web'}
        <script src="[[++assets_url]]components/themebootstrap/js/jquery.min.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/timeline.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/timeline-locales.js"></script>
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script src="[[++assets_url]]components/themebootstrap/js/bootstrap.min.js"></script>
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/add.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]][[*context_key]]/css/timeline.css?v=20180618">
    {/if}

</head>
<body>

<div class="container">
    <section>
        <h1>[[*longtitle:default=`[[*pagetitle]]`]]</h1>
        <div class="formFilter">
            {include "file:$ctx/chunks/formFilter.tpl"}
        </div>
        [[*content]]
    </section>
</div>
<footer class="disclaimer">
    <p>{include "file:$ctx/chunks/footer.tpl"}</p>
</footer>

</body>
</html>
