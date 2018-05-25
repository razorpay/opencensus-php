const { readFileSync, writeFileSync } = require('fs');
const execSync = require('child_process').execSync;
const glob = require('glob').sync;

execSync(
  'rm -rf ' +
    __dirname +
    '/public/js/generated; mkdir -p ' +
    __dirname +
    '/public/js/generated'
);

let content = [].concat
  .apply(
    [],
    [
      'public/js/jquery/jquery-2.1.4.min.js',
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
      'public/js/themes/init.js',
      'public/js/themes/theme.js',
      'public/js/libs/select2.min.js',
      'public/js/libs/angular-recaptcha.js',
      'public/js/merchant/**/*.js',
      'public/js/*.js',
      'web/node_modules/moment/min/moment.min.js',
    ].map(file => glob(file))
  )
  .reduce((content, next) => {
    content += '\n' + String(readFileSync(next));
    return content;
  }, '');

writeFileSync(__dirname + '/public/js/generated/signup.js', content);
