const gulp = require('gulp');
const stylus = require('gulp-stylus');
const iconFontGenerator = require('icon-font-generator/lib');
const glob = require('glob').sync;
const path = require('path');
const { execSync } = require('child_process');

execSync('mkdir -p public/dist/fonts');

function handleError(err) {
  console.log(err.toString());
  this.emit('end');
}

function compileCss(o) {
  if (o && o.path) {
    console.log(path.basename(o.path));
  }
  return gulp
    .src('web/css/*.styl')
    .pipe(
      stylus({
        include: [__dirname + '/node_modules'],
        'include css': true,
      })
    )
    .on('error', handleError)
    .pipe(gulp.dest('public/dist'));
}

function iconFont() {
  iconFontGenerator.generate({
    classPrefix: 'i',
    silent: false,
    types: ['woff', 'woff2'],
    json: false,
    paths: glob('web/icons/*.svg'),
    outputDir: 'public/dist/fonts',
  });
}

gulp.task('watch', () => {
  compileCss();
  iconFont();
  gulp.watch('web/css/**/*.styl', compileCss);
  gulp.watch('web/icons/*.svg', iconFont);
});

gulp.task('default', () => {
  iconFont();
  compileCss();
});
