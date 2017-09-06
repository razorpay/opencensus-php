'use strict';

const path = require('path');
const fs = require('fs');
const execSync = require('child_process').execSync;
const gulp = require('gulp');
const gulpWatch = require('gulp-watch');
const webpack = require('webpack');
const through = require('through2').obj;
const plumber = require('gulp-plumber');
const run = require('run-sequence');
const lazypipe = require('lazypipe');
const dot = require('dot');

const stylus = require('gulp-stylus');
const autoprefixer = require('autoprefixer-stylus');
const bootstrap = require('bootstrap-styl');

const concatMulti = require('gulp-concat-multi');
const uglify = require('gulp-uglify');
const rev = require('gulp-rev');
const webpackConfig = require('./webpack.config.js');

const iconfont = require('gulp-iconfont');
const iconfontCss = require('gulp-iconfont-css');

const revMap = {};
let isDevelopment = false;

function revIt(path) {
  if (path in revMap) {
    path = revMap[path];
  }
  return `/${path}`;
}

// minimal string interpolation for processing tmpl
function interpolate(template, pattern) {
  pattern = pattern || /\{\{asset\(['|"]([^\}]+)['|"]\)\}\}/g;
  return template.replace(pattern, (match, keypath) => {
    return revIt(keypath);
  });
}

function revReference(file, enc, cb) {
  var list = JSON.parse(String(file.contents));
  for (let i in list) {
    revMap[i] = list[i];
  }
  this.push(file);
  cb();
}

function handleError(err) {
  console.log(err.toString());
  this.emit('end');
}

gulp.task('clean', () => {
  execSync('rm -rf public/dist public/js/generated public/css/generated');
});

gulp.task('css', () => {
  gulp
    .src('public/css/style.styl')
    .pipe(plumber({ errorHandler: handleError }))
    .pipe(
      stylus({
        'include css': true,
        use: [bootstrap()],
      })
    )
    .pipe(gulp.dest('public/css/generated'));
});

gulp.task('css:prod', () => {
  return gulp
    .src('public/css/style.styl')
    .pipe(
      stylus({
        'include css': true,
        compress: true,
        use: [bootstrap(), autoprefixer()],
      })
    )
    .pipe(rev())
    .pipe(gulp.dest('public/css/generated'))
    .pipe(rev.manifest())
    .pipe(through(revReference));
});

gulp.task('compileThemes', () => {
  dot.process({
    path: 'public/js/themes/',
    destination: 'public/js/themes/',
    global: 'themes',
  });
});

const concatJs = lazypipe().pipe(concatMulti, {
  'js/generated/pre.js': [
    'public/js/jquery/jquery-2.1.4.min.js',
    'public/js/libs/angular-file-upload-shim.min.js',
    'public/js/angular/angular.min.js',
    'public/js/angular/angular-cookies.min.js',
    'public/js/angular/angular-animate.min.js',
    'public/js/angular/angular-ui-router.min.js',
    'public/js/angular/angular-idle.min.js',
    'public/js/angular/ngStorage.min.js',
    'public/js/angular/ui-load.js',
    'public/js/angular/ui-jq.js',
    'public/js/angular/ui-validate.js',
    'public/js/angular/ui-bootstrap-tpls.min.js',
    'public/js/angular/angular-busy.js',
    'public/js/libs/angular-file-upload.min.js',
    'public/js/libs/filesaver.min.js',
    'public/js/themes/init.js',
    'public/js/themes/theme.js',
    'public/js/libs/select2.min.js',
  ],

  'js/generated/merchant.js': [
    'public/js/libs/angular-recaptcha.js',
    'public/js/merchant/**/*.js',
    'public/js/*.js',
    'node_modules/moment/min/moment.min.js',
  ],

  'js/generated/admin.js': [
    'public/js/angular/ng-react.js',
    'public/js/admin/**/*.js',
    'public/js/*.js',
    'node_modules/moment/min/moment.min.js',
  ],
});

gulp.task('js', () => concatJs().pipe(gulp.dest('public')));

gulp.task('js:prod', () => {
  return concatJs()
    .pipe(uglify())
    .on('error', function(e) {
      throw new Error('Uglify failed', e);
    })
    .pipe(rev())
    .pipe(gulp.dest('public'))
    .pipe(rev.manifest())
    .pipe(through(revReference));
});

gulp.task('tmpl', () => {
  gulp
    .src([
      'resources/views/**/tmpgetIndex.blade.php',
      'resources/views/**/*.blade.php.tmpl',
    ])
    .pipe(
      through(function(file, enc, cb) {
        file.path = file.path.replace(/\/([^\/]+)\.tmpl$/, '/tmp$1');
        file.contents = new Buffer(interpolate(String(file.contents)));
        this.push(file);
        cb();
      })
    )
    .pipe(gulp.dest('resources/views'));
});

const runWebpack = (webpackConfig, cb) => {
  webpack(webpackConfig, (err, stats) => {
    console.log(
      stats.toString({
        colors: true,
      })
    );
    if (stats.hasErrors() && !isDevelopment) {
      throw new Error('Webpack failed');
    }
    cb();
  });
};

var webpackCompiler = null;
gulp.task('webpack:watch', cb => {
  if (!webpackCompiler) {
    webpackCompiler = webpack(webpackConfig('development'));
  }
  webpackCompiler.run(function(err, stats) {
    if (stats.hasErrors()) {
      console.log(
        stats.toString({
          colors: true,
          chunks: false,
        })
      );
    }
    cb();
  });
});

gulp.task('webpack:prod', cb => {
  runWebpack(webpackConfig('production'), cb);
});

gulp.task('dev:setENV', cb => {
  isDevelopment = true;
  cb();
});

gulp.task('default', ['clean'], cb => {
  run('compileThemes', ['css:prod', 'js:prod'], 'webpack:prod', 'tmpl', cb);
});

gulp.task('dev', cb => {
  run('compileThemes', ['css', 'js'], cb);
});

gulp.task('dev:webpack', ['dev:setENV'], cb => {
  run('dev', 'webpack:watch', 'tmpl', cb);
});

const watch = () => {
  gulpWatch('public/js/themes/*.jst', () => {
    run(['compileThemes', 'js']);
  });

  gulpWatch('public/css/*.styl', () => {
    run('css');
  });

  gulpWatch(
    ['public/js/*.js', 'public/js/admin/**/*.js', 'public/js/merchant/**/*.js'],
    () => {
      run('js');
    }
  );

  gulpWatch(
    [
      'public/react/merchant/**/*',
      'public/react/admin/**/*',
      'public/react/rzp/**/*',
      'public/react/styles/**/*.styl',
    ],
    () => {
      run('dev:webpack');
    }
  );
};

gulp.task('watch:full', ['clean', 'dev:webpack'], watch);
gulp.task('watch', ['clean', 'dev:webpack'], watch);

// Gulp task to generate font icons from svg (run: gulp iconfont)
const fontName = 'icons';
gulp.task('iconfont', function() {
  gulp
    .src(['public/react/styles/merchant/svgs/*.svg'])
    .pipe(
      iconfontCss({
        fontName: fontName,
        targetPath: 'style.css',
        fontPath: './',
        cssClass: 'icon',
      })
    )
    .pipe(
      iconfont({
        fontName: fontName,
        formats: ['svg', 'ttf', 'eot', 'woff', 'woff2'], // default, 'woff2' and 'svg' are available
        normalize: true,
        prependUnicode: true, // recommended option
        fontHeight: 1001,
      })
    )
    .pipe(gulp.dest('public/react/styles/fonts/'));
});
