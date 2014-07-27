module.exports = function(grunt){

	"use strict";
	require('load-grunt-tasks')(grunt);	

	var config = grunt.file.readJSON('app/config/grunt.json');

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		env: {
			development: {
				NODE_ENV: 'development',
				DEST: 'dev'
			},
			production: {
				NODE_ENV: 'production',
				DEST: 'prod'
			}
		},

		stylus: {
			production: {
				files: {
					'public/css/style.css': 'public/styl/style.styl'
				},
				options: {
					compress: true
				}
			},
			development: {
				files: {
					'public/css/style.css': 'public/styl/style.styl'
				},
				options: {
					compress: false
				}
			}
		},

		jshint: {
			development: ['public/js/main.js', 'public/js/activation.js'],
			production: []
		},

		concat: {
			development: {
				files: {
					'public/css/dev/style.css': ['public/css/lib/simplegrid.css','public/css/lib/pikaday.css','public/css/lib/reset.css','public/css/fonts.css','public/css/style.css'],
					'public/js/dev/pre.js': ['public/js/lib/jquery.min.js','public/js/lib/moment.min.js','public/js/lib/pikaday.js','public/js/lib/highcharts.min.js','public/js/lib/path.min.js'],
					'public/js/dev/post.js': ['public/js/main.js'],
					'public/js/dev/activation.js': ['public/js/lib/bootstrap.fileinput.js','public/js/activation.js']
				}
			},
			production: {}
		},

		cssmin: {
			development: {},
			production: {
				src: ['public/css/lib/simplegrid.css','public/css/lib/pikaday.css','public/css/lib/reset.css','public/css/fonts.css','public/css/style.css'],
				dest: 'public/css/prod/style.css'
			}
		},

		uglify: {
			development: {},
			production: {
				files: {
					'public/js/prod/pre.js': ['public/js/lib/jquery.min.js','public/js/lib/moment.min.js','public/js/lib/pikaday.js','public/js/lib/highcharts.min.js','public/js/lib/path.min.js'],
					'public/js/prod/post.js': ['public/js/main.js'],
					'public/js/prod/activation.js': ['public/js/lib/bootstrap.fileinput.js','public/js/activation.js']
				}
			}
		},

		preprocess: {
			layout: {
				src: 'app/views/templates/layout.blade.php.tmpl',
				dest: 'app/views/layoutGenerated.blade.php'
			},
			activation: {
				src: 'app/views/templates/merchants/getActivation.blade.php.tmpl',
				dest: 'app/views/merchants/getActivationGenerated.blade.php'
			}
		},

		hashres: {
			options: {
				encoding: 'utf8',
				fileNameFormat: '${name}.${hash}.${ext}',
				renameFiles: true
			},
			development: {
				src: ['public/css/dev/style.css','public/js/dev/pre.js','public/js/dev/post.js','public/js/dev/activation.js'],
				dest: ['app/views/layoutGenerated.blade.php', 'app/views/merchants/getActivationGenerated.blade.php']
			},
			production: {
				src: ['public/css/prod/style.css','public/js/prod/pre.js','public/js/prod/post.js','public/js/prod/activation.js'],
				dest: ['app/views/layoutGenerated.blade.php', 'app/views/merchants/getActivationGenerated.blade.php']
			}
		},

		clean: {
			development: ['public/css/dev/*.css'],
			production: ['public/css/prod/*.css']
		}

	});

	grunt.registerTask('default',   ['env:'+config.environment,'clean:'+config.environment,'stylus:'+config.environment,'jshint:'+config.environment,'concat:'+config.environment,'cssmin:'+config.environment,'uglify:'+config.environment,'preprocess','hashres:'+config.environment]);
};
