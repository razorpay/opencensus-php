const { readFileSync, writeFileSync } = require('fs');
const execSync = require('child_process').execSync;
const glob = require('glob').sync;

execSync('rm -rf public/js/generated; mkdir -p public/js/generated');

let content = [].concat
  .apply(
    [],
    [
      'public/js/angular/angular.min.js',
      'public/js/angular/angular-cookies.min.js',
      'public/js/angular/angular-animate.min.js',
      'public/js/angular/angular-ui-router.min.js',
      'public/js/angular/angular-idle.min.js',
      'public/js/angular/ngStorage.min.js',
      'public/js/angular/ui-load.js',
      'public/js/angular/ui-validate.js',
      'public/js/angular/angular-busy.js',
      'public/js/themes/init.js',
      'public/js/themes/theme.js',
      'public/js/merchant/**/*.js',
      'public/js/filters.js',
      'public/js/services.js',
    ].map((file) => glob(file)),
  )
  .reduce((content, next) => {
    content += '\n' + String(readFileSync(next));
    return content;
  }, '');

content = `var __VERSION__ = "${process.env.VERSION || 'signup'}";${content}`;
writeFileSync('public/js/generated/signup.js', content);
