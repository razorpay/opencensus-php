<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="UTF-8">
    <title>API Inspector</title>
    {!! $renderer->renderHead() !!}
    <style>
        div.phpdebugbar { top: 0 !important; height: 100% !important; }
        div.phpdebugbar-drag-capture { display: none !important; }
        div.phpdebugbar-resize-handle { display: none !important; }
        div.phpdebugbar-body { height: 100% !important; }
        div.phpdebugbar-openhandler { z-index: 100001 !important; }
        div.phpdebugbar-openhandler-overlay { z-index: 100000 !important; }
        a.phpdebugbar-close-btn,a.phpdebugbar-minimize-btn,a.phpdebugbar-maximize-btn { display: none !important; }
        div.phpdebugbar-header-right { padding-right: 20px !important; }
    </style>
</head>
<body>
{!! $renderer->render() !!}
</body>
</html>
