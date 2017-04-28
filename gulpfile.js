'use strict';

const gulp = require('gulp');
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

const revMap = {};
let isDevelopment = false;

// functions and variables to be passed to blade.php.tmpl file
const tmplData = {
  asset: function(path) {
    if (path in revMap) {
      path = revMap[path];
    }
    return `/${path}`;
  },
};

// minimal string interpolation for processing tmpl
function interpolate(template, pattern) {
  pattern = pattern || /\{\{([^\}]+)\}\}/g;
  return template.replace(pattern, (match, keypath) => {
    return new Function('_', 'return _.' + keypath.trim())(tmplData);
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

gulp.task('cssnew', () => {
  gulp
    .src('public/react/merchant/styles/app.styl')
    .pipe(plumber({ errorHandler: handleError }))
    .pipe(
      stylus({
        include: 'node_modules',
        'include css': true,
        use: [bootstrap()],
      })
    )
    .pipe(gulp.dest('public/css/generated/new'));
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
    'public/js/angular/ng-react.js',
    'public/js/libs/angular-file-upload.min.js',
    'public/js/libs/angulartics.min.js',
    'public/js/libs/angulartics-segmentio.min.js',
    'public/js/libs/filesaver.min.js',
    'public/js/libs/jquery-tourbus.js',
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

  'js/generated/merchant_react.js': ['public/react/dist/merchant_react.js'],
  'js/generated/merchant_new_react.js': [
    'public/js/jquery/jquery-2.1.4.min.js',
    'public/react/dist/merchant_new_react.js',
  ],

  'js/generated/admin_react.js': ['public/react/dist/admin_react.js'],

  'js/generated/admin.js': [
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
      console.log(e);
    })
    .pipe(rev())
    .pipe(gulp.dest('public'))
    .pipe(rev.manifest())
    .pipe(through(revReference));
});

gulp.task('tmpl', () => {
  gulp
    .src('resources/views/**/*.blade.php.tmpl')
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

gulp.task('dev', () => {
  run('compileThemes', ['css', 'cssnew', 'js'], 'tmpl');
});

gulp.task('reactRevReplace', () => {
  return gulp
    .src(`public/${revMap['js/generated/merchant.js']}`)
    .pipe(
      through(function(file, enc, cb) {
        file.contents = new Buffer(
          interpolate(String(file.contents), /\<\%([^\}]+)\%\>/g)
        );
        this.push(file);
        cb();
      })
    )
    .pipe(gulp.dest('public/js/generated'));
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

gulp.task('webpack', cb => {
  runWebpack(Object.create(webpackConfig), cb);
});

var webpackCompiler = null;
gulp.task('webpack:watch', cb => {
  if (!webpackCompiler) {
    webpackCompiler = webpack(Object.assign({}, webpackConfig));
  }
  webpackCompiler.run(function(err, stats) {
    console.log(
      stats.toString({
        colors: true,
        chunks: false,
      })
    );
    cb();
  });
});

gulp.task('webpack:prod', cb => {
  let config = Object.create(webpackConfig);
  config.plugins = config.plugins.concat(
    new webpack.DefinePlugin({
      'process.env': {
        NODE_ENV: JSON.stringify('production'),
      },
    }),
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        warnings: false,
      },
      output: {
        comments: false,
      },
    })
  );

  runWebpack(config, cb);
});

gulp.task('dev:setENV', cb => {
  isDevelopment = true;
  cb();
});

gulp.task('default', cb => {
  run(
    'webpack:prod',
    'compileThemes',
    ['css:prod', 'js:prod'],
    'tmpl',
    'reactRevReplace',
    cb
  );
});

gulp.task('dev', cb => {
  run(['css', 'js'], 'tmpl', cb);
});

gulp.task('dev:webpack', ['dev:setENV'], cb => {
  run('webpack:watch', 'dev', cb);
});

gulp.task('watch:full', ['dev:webpack'], () => {
  gulp.watch('public/css/*.styl', ['css']);
  gulp.watch('public/react/styles/*.styl', ['cssnew']);
  gulp.watch('public/js/themes/*.jst', ['compileThemes', 'js']);
  gulp.watch(
    [
      'public/js/*.js',
      'public/js/admin/**/*.js',
      'public/js/merchant/**/*.js',
      'public/react/merchant/**/*',
      'public/react/admin/**/*',
      'public/react/rzp/**/*',
    ],
    ['dev:webpack']
  );
});

gulp.task('watch', ['dev'], () => {
  gulp.watch('public/css/*.styl', ['css']);
  gulp.watch(
    ['public/js/*.js', 'public/js/admin/**/*.js', 'public/js/merchant/**/*.js'],
    ['js']
  );
});
