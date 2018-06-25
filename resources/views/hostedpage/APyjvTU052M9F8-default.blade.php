<?php date_default_timezone_set('Asia/Kolkata') ?>
<!doctype html>
<html>
<head>
    <title>Schindler</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1,maximum-scale=1">
    @if (empty($udf_schema) === false)
        <script src="https://cdn.jsdelivr.net/npm/{{'@'}}json-editor/json-editor/dist/jsoneditor.min.js"></script>
    @endif
</head>
<body>
<h2>Test</h2>
<div id="udf_container">
</div>
<script>
    var element = document.getElementById('udf_container');
    var editor = new JSONEditor(element, {
      disable_properties: true,
      disable_edit_json: true,
      disable_collapse: true,
      disable_array_reorder: true,
      disable_array_delete: true,
      disable_array_add: true,
      schema: {!! $udf_schema !!}
    });
    editor.on('change', function() {
        var json = editor.getValue();

        alert(JSON.stringify(json,null,2));

    });
</script>
