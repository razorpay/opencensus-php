require('../signup');

const gulp = require('gulp');
const stylus = require('gulp-stylus');
const iconFontGenerator = require('icon-font-generator/lib');
const glob = require('glob').sync;
const path = require('path');
const { execSync } = require('child_process');
const isProd = require('process').env.NODE_ENV === 'production';

function createBaseDir() {
  execSync(`
    mkdir -p ../public/dist/css;
    cp entry/* ../public/dist/
  `);
}

function handleError(err) {
  console.log(err.toString());
  this.emit('end');
  isProd && process.exit(1);
}

function compileCss(o) {
  if (o && o.path) {
    console.log(path.basename(o.path));
  }
  return gulp
    .src('css/*.styl')
    .pipe(
      stylus({
        include: [
          __dirname + '/../public/dist/css',
          __dirname + '/node_modules',
          __dirname + '/node_modules/bootstrap-styl',
        ],
        'include css': true,
        compress: isProd,
      })
    )
    .on('error', handleError)
    .pipe(gulp.dest('../public/dist/css'));
}

function iconFont(cb) {
  Promise.all(
    glob('icons/*').map(
      folderName =>
        new Promise((resolve, reject) =>
          iconFontGenerator.generate(
            {
              cssTemplate: 'templates/icons.hbs',
              classPrefix: 'i',
              silent: false,
              types: ['woff', 'woff2'],
              json: false,
              paths: glob(`${folderName}/*.svg`),
              outputDir: '../public/dist/css',
              fontName: `${path.basename(folderName)}-icons`,
            },
            resolve
          )
        )
    )
  ).then(cb);
}

gulp.task('watch', () => {
  createBaseDir();
  execSync('rm -rf ../public/dist/css/assets');
  execSync('ln -s ../../../web/css/assets ../public/dist/css/assets');
  iconFont(compileCss);
  gulp.watch('css/**/*.styl', compileCss);
  gulp.watch('icons/*.svg', _ => iconFont(compileCss));
  gulp.watch('js/component/**/*.js', e => {
    if (e.type === 'added' || e.type === 'deleted' || e.type === 'renamed') {
      workbenchServer();
    }
  })

  require('livereload')
    .createServer()
    .watch([
      __dirname + '/../public/dist/css',
      __dirname + '/js/component',
      __dirname + '/../public/workbench/index.php',
    ]);

  workbenchServer();

});

gulp.task('default', () => {
  execSync('rm -rf ../public/dist');
  createBaseDir();
  execSync('cp -r css/assets ../public/dist/css');
  iconFont(compileCss);
});

const webpackConfig = require('./webpack.config');
webpackConfig.output.filename = '[name]';
webpackConfig.output.library = 'component';
webpackConfig.output.libraryTarget = 'umd';

let workbenchApp;

function workbenchServer() {
  let entry = {};
  glob('js/component/**/*.js').forEach(f => { entry[f] = './' + f });
  webpackConfig.entry = entry;

  const middleware = require('webpack-dev-middleware');
  const compiler = require('webpack')(webpackConfig);

  if (workbenchApp) {
    workbenchApp.close();
    console.log('restarting workbench');
  }
  workbenchApp = require('express')();

  workbenchApp.use(middleware(compiler));

  workbenchApp = workbenchApp.listen(3000);
}
