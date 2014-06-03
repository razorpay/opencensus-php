module.exports = function(grunt){

	"use strict";
	require("matchdep").filterDev("grunt-*").forEach(grunt.loadNpmTasks);

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
			compile: {
				files: {
					'public/css/style.css': 'public/styl/style.styl'
				}
			}
		},

		jshint: {
			development: [],
			production: []
		},

		concat: {
			development: {
				files: {
					'public/css/dev/style.css': ['public/css/lib/simplegrid.css','public/css/lib/reset.css','public/css/fonts.css','public/css/style.css'],
				}
			},
			production: {}
		},

		cssmin: {
			development: {},
			production: {
				src: ['public/css/lib/simplegrid.css','public/css/lib/reset.css','public/css/fonts.css','public/css/style.css'],
				dest: 'public/css/prod/style.css'
			}
		},

		uglify: {
			development: {},
			production: {
				files: {}
			}
		},

		preprocess: {
			layout: {
				src: 'app/views/templates/layout.blade.php.tmpl',
				dest: 'app/views/layout.blade.php'
			}
		},

		hashres: {
			options: {
				encoding: 'utf8',
				fileNameFormat: '${name}.${hash}.${ext}',
				renameFiles: true
			},
			development: {
				src: ['public/css/dev/style.css'],
				dest: ['app/views/layout.blade.php']
			},
			production: {
				src: ['public/css/prod/style.css'],
				dest: ['app/views/layout.blade.php']
			}
		},

		clean: {
			development: ['public/css/dev/*.css'],
			production: ['public/css/prod/*.css']
		}

	});

	grunt.registerTask('default',   ['env:'+config.environment,'clean:'+config.environment,'stylus','jshint:'+config.environment,'concat:'+config.environment,'cssmin:'+config.environment,'uglify:'+config.environment,'preprocess','hashres:'+config.environment]);

};
