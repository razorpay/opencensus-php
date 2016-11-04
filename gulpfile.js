'use strict'

const gulp = require('gulp')
const webpack = require('webpack')
const through = require('through')
const plumber = require('gulp-plumber')
const run = require('run-sequence')
const lazypipe = require('lazypipe')
const stylus = require('gulp-stylus')
const cssnano = require('gulp-cssnano')
const bootstrap = require('bootstrap-styl')
const autoprefixer = require('gulp-autoprefixer')
const concatMulti = require('gulp-concat-multi')
const uglify = require('gulp-uglify')
const rev = require('gulp-rev')
const webpackConfig = require('./webpack.config.js')

const revMap = {}

// functions and variables to be passed to blade.php.tmpl file
const tmplData = {
  asset: function(path) {
    if (path in revMap) {
      path = revMap[path];
    }
    return `/${path}`
  }
}

// minimal string interpolation for processing tmpl
function interpolate(template) {
  return template.replace(/\{\{([^\}]+)\}\}/g, (match, keypath)=> {
    return new Function('_', 'return _.' + keypath.trim())(tmplData);
  })
}

function revReference(file) {
  var list = JSON.parse(String(file.contents));
  for (let i in list) {
    revMap[i] = list[i];
  }
  this.emit('data', file)
}

const stylus2css = lazypipe()
  .pipe(plumber)
  .pipe(stylus, {
    'include css': true,
    use: bootstrap()
  })


gulp.task('css', ()=> {
  gulp.src('public/css/style.styl')
    .pipe(stylus2css())
    .pipe(gulp.dest('public/css/generated'))
})

gulp.task('css:prod', ()=> {
  return gulp.src('public/css/style.styl')
    .pipe(stylus2css())
    .pipe(cssnano())
    .pipe(autoprefixer())
    .pipe(rev())
    .pipe(gulp.dest('public/css/generated'))
    .pipe(rev.manifest())
    .pipe(through(revReference))
})

const concatJs = lazypipe()
  .pipe(concatMulti, {
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
      'public/js/libs/angulartics.min.js',
      'public/js/libs/angulartics-segmentio.min.js',
      'public/js/libs/filesaver.min.js',
      'public/js/libs/jquery-tourbus.js',
    ],

    'js/generated/merchant.js': [
      'public/js/libs/angular-recaptcha.js',
      'public/js/merchant/**/*.js',
      'public/js/*.js',
      'public/js/libs/moment.min.js',
      'public/react/dist/merchant_react.js',
      'node_modules/ngreact/ngReact.js'
    ],

    'js/generated/admin.js': [
      'public/js/admin/**/*.js',
      'public/js/*.js',
      'public/js/libs/moment.min.js'
    ]
  })


gulp.task('js', () => concatJs().pipe(gulp.dest('public')))

gulp.task('js:prod', () => {
  return concatJs()
    .pipe(uglify())
    .on('error', function(e){
        console.log(e);
     })
    .pipe(rev())
    .pipe(gulp.dest('public'))
    .pipe(rev.manifest())
    .pipe(through(revReference))
})

gulp.task('tmpl', ()=> {
  gulp.src('resources/views/**/*.blade.php.tmpl')
    .pipe(through(function(file) {
      file.path = file.path.replace(/\/([^\/]+)\.tmpl$/, '/tmp$1');
      file.contents = new Buffer(interpolate(String(file.contents), tmplData));
      this.emit('data', file)
    }))
    .pipe(gulp.dest('resources/views'))
})

const runWebpack = (webpackConfig, cb) => {
  webpack(webpackConfig, (err, stats) => {
    if (err) throw new Error(err)
    console.log(stats.toString({
      colors: true
    }))
    cb()
  })
}

gulp.task('webpack', (cb) => {
  runWebpack(Object.create(webpackConfig), cb)
})

gulp.task('webpack:prod', (cb) => {
  let config = Object.create(webpackConfig)
  config.plugins = config.plugins.concat(
    new webpack.DefinePlugin({
      'process.env': {
        NODE_ENV: JSON.stringify('production')
      }
    }),
    new webpack.optimize.DedupePlugin(),
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        warnings: false
      },
      output: {
        comments: false
      }
    })
  )

  runWebpack(config, cb)
})

gulp.task('default', ()=> {
  run('webpack:prod', ['css:prod', 'js:prod'], 'tmpl')
})

gulp.task('dev', ()=> {
  run('webpack', ['css', 'js'], 'tmpl')
})

gulp.task('watch', ['dev'], ()=> {
  gulp.watch('public/css/*.styl', ['css'])
  gulp.watch([
    'public/js/*.js',
    'public/js/admin/**/*.js',
    'public/js/merchant/**/*.js',
  ], ['js'])
  gulp.watch('public/react/**/*', ['webpack', 'js'])
})
