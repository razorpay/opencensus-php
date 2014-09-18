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

		concat: {
			development: {
				files: {
					'public/css/dev/style.css': [
													'public/css/bootstrap.css',
													'public/css/animate.css',
													'public/css/font-awesome.min.css',
													'public/css/simple-line-icons.css',
													'public/css/font.css',
													'public/css/app.css'
												],
					'public/js/dev/pre.js': [
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
					'public/js/dev/merchant.js': [
													'public/js/app.js',
													'public/js/services.js',
													'public/js/controllers.js',
													'public/js/controllers/*.js',
													'public/js/filters.js',
													'public/js/directives.js'
												],
					'public/js/dev/admin.js': [
													'public/js/admin/app.js',
													'public/js/services.js',
													'public/js/admin/controllers.js',
													'public/js/admin/controllers/*.js',
													'public/js/filters.js',
													'public/js/directives.js'
											]
				}
			},
			production: {}
		},

		cssmin: {
			development: {},
			production: {
				files: {
					'public/css/prod/style.css': [
													'public/css/bootstrap.css',
													'public/css/animate.css',
													'public/css/font-awesome.min.css',
													'public/css/simple-line-icons.css',
													'public/css/font.css',
													'public/css/app.css'
												]
				}
			}
		},

		uglify: {
			development: {},
			production: {
				files: {
					'public/js/prod/pre.js': [
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
					'public/js/prod/merchant.js': [
													'public/js/app.js',
													'public/js/services.js',
													'public/js/controllers.js',
													'public/js/controllers/*.js',
													'public/js/filters.js',
													'public/js/directives.js'
												],
					'public/js/prod/admin.js': [
													'public/js/admin/app.js',
													'public/js/services.js',
													'public/js/admin/controllers.js',
													'public/js/admin/controllers/*.js',
													'public/js/filters.js',
													'public/js/directives.js'
												]	
				}
			}
		},

		preprocess: {
			admin: {
				src: 'app/views/admin/getIndex.php.tmpl',
				dest: 'app/views/admin/getIndexGenerated.php'
			},
			merchant: {
				src: 'app/views/merchant/getIndex.php.tmpl',
				dest: 'app/views/merchant/getIndexGenerated.php'
			}
		},

		hashres: {
			options: {
				encoding: 'utf8',
				fileNameFormat: '${name}.${hash}.${ext}',
				renameFiles: true
			},
			development: {
				src: ['public/css/dev/style.css','public/js/dev/pre.js','public/js/dev/merchant.js','public/js/dev/admin.js'],
				dest: ['app/views/admin/getIndexGenerated.php', 'app/views/merchant/getIndexGenerated.php']
			},
			production: {
				src: ['public/css/prod/style.css','public/js/prod/pre.js','public/js/prod/merchant.js','public/js/prod/admin.js'],
				dest: ['app/views/admin/getIndexGenerated.php', 'app/views/merchant/getIndexGenerated.php']
			}
		},

		clean: {
			development: ['public/css/dev/*.css', 'public/js/dev/*.js'],
			production: ['public/css/prod/*.css', 'public/js/prod/*.js']
		}

	});

	grunt.registerTask('default',   ['env:'+config.environment,'clean:'+config.environment,'concat:'+config.environment,'cssmin:'+config.environment,'uglify:'+config.environment,'preprocess','hashres:'+config.environment]);
};
