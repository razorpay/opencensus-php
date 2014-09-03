<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
    <meta charset="utf-8">
    <meta name="google" value="notranslate" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Razorpay Merchant Dashboard">
    <meta name="author" content="Razorpay">
    <link rel="shortcut icon" href="img/logo.png">
    <title>Razorpay - Admin Panel</title>
    <meta name="description" content="app, web app, responsive, responsive layout, admin, admin panel, admin dashboard, flat, flat ui, ui kit, AngularJS, ui route, charts, widgets, components" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="stylesheet" href="css/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="css/animate.css" type="text/css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/font.css" type="text/css" />
    <link rel="stylesheet" href="css/app.css" type="text/css" />
</head>
<body ng-controller="AppCtrl">
    <div class="app" id="app" ng-class="{'app-header-fixed':app.settings.headerFixed, 'app-aside-fixed':app.settings.asideFixed, 'app-aside-folded':app.settings.asideFolded}" ui-view></div>
    <!-- jQuery -->
    <script src="js/jquery/jquery.min.js"></script>
    <!-- Angular -->
    <script src="js/libs/angular-file-upload-shim.min.js"></script>
    <script src="js/angular/angular.min.js"></script>
    <script src="js/angular/angular-cookies.min.js"></script>
    <script src="js/angular/angular-animate.min.js"></script>
    <script src="js/angular/angular-ui-router.min.js"></script>
    <script src="js/angular/angular-translate.js"></script>
    <script src="js/angular/angular-idle.min.js"></script>
    <script src="js/angular/ngStorage.min.js"></script>
    <script src="js/angular/ui-load.js"></script>
    <script src="js/angular/ui-jq.js"></script>
    <script src="js/angular/ui-validate.js"></script>
    <script src="js/angular/ui-bootstrap-tpls.min.js"></script>
    <script src="js/libs/angular-file-upload.min.js"></script>
    <!-- App -->
    <script src="js/admin/app.js"></script>
    <script src="js/services.js"></script>
    <script src="js/admin/controllers.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/directives.js"></script>
    <script>
    angular.module("app").constant("CSRF_TOKEN", '<?php echo csrf_token(); ?>');
    </script>
    <!-- Lazy loading -->

    
</body>
</html>