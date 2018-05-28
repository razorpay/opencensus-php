<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/dist/css/merchant.css">
  <link rel="stylesheet" href="/dist/css/merchant-icons.css">
  <?php
    if (!isset($_GET['path'])) {
      $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('../../web/js/component'));
      foreach ($rii as $file) {
        if (!$file->isDir()){
          $file = substr($file, 23, -3);
          echo '<a href="?path='.$file.'">'.$file.'</a><br>';
        }
      }
      die();
    }
  ?>
  <style>
    html {
      height: 100%;
    }
    body {
      text-align: center;
      height: 100%;
      white-space: nowrap;
      background-color: #eee;
      background-image: repeating-linear-gradient(0deg, transparent, transparent 15px,
                                                  rgba(0, 0, 0, 0.07) 1px, transparent 16px),
                        repeating-linear-gradient(90deg, transparent, transparent 15px,
                                                  rgba(0, 0, 0, 0.07) 1px, transparent 16px);
    }
    body:after {
      display: inline-block;
      vertical-align: middle;
      content: '';
      height: 100%;
    }
    #react-root {
      white-space: normal;
      text-align: left;
      background: #fff;
      display: inline-block;
      vertical-align: middle;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
    }
  </style>
  <script src="/dist/vendor_m.js"></script>
  <script src="http://localhost:35729/livereload.js?snipver=1"></script>
</head>
<body>
  <div id="react-root">
  </div>
  <script src="http://localhost:3000/js/component/<?=$_GET['path']?>.js"></script>
  <script>
    ReactDOM.render(React.createElement(component.default),
    document.getElementById('react-root'));
  </script>
