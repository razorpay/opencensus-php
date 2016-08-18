module.exports = function(grunt){

  "use strict";
  require('load-grunt-tasks')(grunt);

  var config = grunt.file.readJSON('config/grunt.json');

  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    env: {
      development: {
        NODE_ENV: 'development',
        DEST: 'generated'
      },
      production: {
        NODE_ENV: 'production',
        DEST: 'generated'
      }
    },

    stylus: {
      compile: {
        options: {
          'include css': true,
          use: [
            require('bootstrap-styl')
          ],
        },
        files: {
          'public/css/generated/style.css': 'public/css/app.styl'
        }
      }
    },

    postcss: {
      beta: {},
      development: {},
      production: {
        options: {
          processors: [
            require('autoprefixer')({browsers: 'last 10 versions'}), // add vendor prefixes
            require('cssnano')() // minify the result
          ]
        },
        dist: {
          src: 'public/css/generated/app.css'
        }
      }
    },

    concat: {
      dist: {
        files: {
          'public/js/generated/pre.js': [
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
            'public/js/libs/jquery-tourbus.js'
          ],

          'public/js/generated/merchant.js': [
            'public/js/libs/angular-recaptcha.js',
            'public/js/merchant/**/*.js',
            'public/js/*.js',
            'public/js/libs/moment.min.js'
          ],

          'public/js/generated/admin.js': [
            'public/js/admin/**/*.js',
            'public/js/*.js',
            'public/js/libs/moment.min.js'
          ]
        }
      }
    },

    uglify: {
      beta: {},
      development: {},
      production: {
        files: [{
          expand: true,
          cwd: 'public/js/generated/',
          src: '*.js',
          dest: 'public/js/generated/'
        }]
      }
    },

    preprocess: {
      phpTemplateFiles: {
        files: [{
          expand: true,
          cwd: 'resources/views/',
          src: ['**/*.blade.php.tmpl'],
          dest: 'resources/views/',
          ext: '.blade.php',
          rename: function(dest, src) {
            var index = src.lastIndexOf("/") + 1;
            src = src.substr(0, index) + 'tmp' + src.substr(index);
            console.log(dest + src);
            return dest + src;
          }
        }]
      },
      options: {
        context: {
          DEST: 'generated'
        }
      },
    },

    hashres: {
      options: {
        encoding: 'utf8',
        fileNameFormat: '${name}.${hash}.${ext}',
        renameFiles: true
      },
      dist: {
        src: ['public/css/generated/style.css','public/js/generated/*.js'],
        dest: ['resources/views/**/tmp*.blade.php']
      },
    },

    clean: {
      css: 'public/css/generated/*.css',
      js: 'public/js/generated/*.js'
    },

    watch: {
      styl: {
        files: 'public/css/*.styl',
        tasks: ['clean:css', 'stylus', 'hashres'],
        options: {
          interrupt: true
        }
      },

      js : {
        files: [
          'public/js/*.js',
          'public/js/admin/**/*.js',
          'public/js/merchant/**/*.js'
        ],
        tasks: ['clean:js', 'concat', 'hashres'],
        options: {
          interrupt: true
        }
      }
    }
  });

  grunt.registerTask(
    'default',
    [
      'clean',
      'concat',
      'stylus',
      'postcss:' + config.environment,
      'uglify:' + config.environment,
      'preprocess',
      'hashres'
    ]
  );
};
