module.exports = function(grunt){

    "use strict";
    require('load-grunt-tasks')(grunt);

    var config = grunt.file.readJSON('app/config/grunt.json');

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

        concat: {
            dist: {
                files: {
                    'public/css/generated/style.css': [
                        'public/css/bootstrap.css',
                        'public/css/animate.css',
                        'public/css/font-awesome.min.css',
                        'public/css/simple-line-icons.css',
                        'public/css/font.css',
                        'public/css/app.css'
                    ],

                    'public/js/generated/pre.js': [
                        'public/js/jquery/jquery.min.js',
                        'public/js/libs/angular-file-upload-shim.min.js',
                        'public/js/angular/angular.min.js',
                        'public/js/angular/angular-cookies.min.js',
                        'public/js/angular/angular-animate.min.js',
                        'public/js/angular/angular-ui-router.min.js',
                        'public/js/angular/angular-translate.js',
                        'public/js/angular/angular-idle.min.js',
                        'public/js/angular/ngStorage.min.js',
                        'public/js/angular/ui-load.js',
                        'public/js/angular/ui-jq.js',
                        'public/js/angular/ui-validate.js',
                        'public/js/angular/ui-bootstrap-tpls.min.js',
                        'public/js/angular/angular-busy.js',
                        'public/js/libs/angular-file-upload.min.js'
                    ],

                    'public/js/generated/merchant.js': [
                        'public/js/merchant/**/*.js',
                        'public/js/*.js',
                        'public/js/libs/moment.min.js'
                    ],

                    'public/js/generated/admin.js': [
                        'public/js/admin/**/*.js',
                        'public/js/*.js'
                    ]
                }
            }
        },

        cssmin: {
            development: {},
            production: {
                files: {
                    'public/css/generated/style.css': [ 'public/css/generated/style.css' ]
                }
            }
        },

        uglify: {
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
                    cwd: 'app/views/',
                    src: ['**/*.blade.php.tmpl'],
                    dest: 'app/views/',
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
                dest: ['app/views/**/tmp*.blade.php']
            },
        },

        clean: ['public/css/generated/*.css', 'public/js/generated/*.js'],

        watch: {
            files: [
                'public/js/*.js',
                'public/js/admin/**/*.js',
                'public/js/merchant/**/*.js',
                'public/css/*.css'
                ],
            tasks: 'default',
            options: {
                spawn: true,
                interrupt: true
            }
        }
    });

    grunt.registerTask(
        'default',
        [
            'clean',
            'concat',
            'cssmin:'+config.environment,
            'uglify:'+config.environment,
            'preprocess',
            'hashres'
        ]
    );
};
