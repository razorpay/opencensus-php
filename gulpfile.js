const gulp = require('gulp');
const stylus = require('gulp-stylus');
const iconFontGenerator = require('icon-font-generator/lib');
const glob = require('glob').sync;
const path = require('path');
const { execSync } = require('child_process');

execSync('mkdir -p public/dist/css');

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
        include: [__dirname + '/public/dist/css'],
        'include css': true,
      })
    )
    .on('error', handleError)
    .pipe(gulp.dest('public/dist/css'));
}

function iconFont(cb) {
  iconFontGenerator.generate(
    {
      classPrefix: 'i',
      silent: false,
      types: ['woff', 'woff2'],
      json: false,
      paths: glob('web/icons/*.svg'),
      outputDir: 'public/dist/css',
    },
    cb
  );
}

gulp.task('watch', () => {
  iconFont(compileCss);
  gulp.watch('web/css/**/*.styl', compileCss);
  gulp.watch('web/icons/*.svg', iconFont);
});

gulp.task('default', () => {
  iconFont(compileCss);
});
