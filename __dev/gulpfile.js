/**
 * HOW-TO-USE
 * run "gulp doc" to view the documentation
 */
var gulp = require('gulp');
var concat = require('gulp-concat');
var changed = require('gulp-changed');
var gulpUtil = require('gulp-util');
var uglify = require('gulp-uglify');
var merge2 = require('merge2');
var stripComments = require('gulp-strip-comments');
var vueTpl2Js = require('gulp-vue-template2js');

console.log(gulpUtil.colors.black.bgGreen(' Run "gulp doc" to see the gulpfile-documentation                               '));

var settings = {
	srcFolder : '.',
	buildFolder : './../js/app',
	
	minifyVendor:true,
	minifyApp:false,
	vendor: {
		js: [
			'node_modules/vue/dist/vue.min.js',
			'node_modules/axios/dist/axios.min.js',
			'node_modules/cropperjs/dist/cropper.min.js',
		],
		css: [
			'node_modules/cropperjs/dist/cropper.min.css',
		],
		assets: [
		]
	}
};


/**
 * load custom settings
 */
console.log(gulpUtil.colors.black.bgYellow('--------------------------------------------------------------------------------'));
try {
	if(gulpUtil.env._ !==undefined && gulpUtil.env._[0] === 'deploy') {
		console.log(gulpUtil.colors.black.bgYellow(' DEPLOY - MODE                                                                  '));
		throw new Exception('deploy-mode');
	}
	
	var customSettings = require('./developmentSettings.js');
	if(customSettings.settings!==undefined) {
		settings = extend(settings,customSettings.settings);
	}
	console.log(gulpUtil.colors.black.bgYellow(' Custom settings loaded.                                                        '));
} catch (e) {
	console.log(gulpUtil.colors.black.bgYellow(' No custom settings, proceed with default gulp settings.                        '));
}
console.log(gulpUtil.colors.black.bgYellow('--------------------------------------------------------------------------------'));


gulp.task('doc',function() {
	console.log(gulpUtil.colors.black.bgGreen('--------------------------------------------------------------------------------'));
	console.log(gulpUtil.colors.black.bgGreen(' DOCUMENTATION                                                                  '));
	console.log(gulpUtil.colors.black.bgGreen(' "gulp watch" - run while develop, will build inside the build-folder           '));
	console.log(gulpUtil.colors.black.bgGreen(' "gulp build" - run to build the project once, in the build-folder              '));
	console.log(gulpUtil.colors.black.bgGreen(' "gulp clean" - run to clean the build-folder                                   '));
	console.log(gulpUtil.colors.black.bgGreen(' "gulp deploy"- build with default settings                                     '));
	console.log(gulpUtil.colors.black.bgGreen('                                                                                '));
	console.log(gulpUtil.colors.black.bgGreen(' You can create a "developmentSettings.js" to override the settings.            '));
	console.log(gulpUtil.colors.black.bgGreen('--------------------------------------------------------------------------------'));
});




/** START vendor ****************************************** **/

gulp.task('vendor.misc', function() {
	var files = [];
	files = files.concat(settings.vendor.js);
	files = files.concat(settings.vendor.css);
	files = files.concat(settings.vendor.assets);
	return gulp.src(files)
		.pipe(changed(settings.buildFolder + '/vendor'))
		.pipe(gulp.dest(settings.buildFolder + '/vendor'));
});
gulp.task('vendor', ['vendor.misc'], function() {});
/** END vendor ****************************************** **/


/** START app ****************************************** **/
gulp.task('app.scripts', function() {
	return gulp.src([ settings.srcFolder+ '/app/**/*.js', '!'+settings.srcFolder + '/app/**/*.test.js' ])
		.pipe(vueTpl2Js())
		.pipe(settings.minifyApp ? uglify({ mangle:true }) : gulpUtil.noop() )
		.pipe(concat('app.js'))
		.on('error', swallowError)
		.pipe(gulp.dest(settings.buildFolder));
});


gulp.task('app',['app.scripts'],function() {});
/** END app ****************************************** **/

gulp.task('build', ['vendor','app'], function() {});
gulp.task('deploy', ['build'],function() {});

/**
 * watch for changes and kick some tasks
 */
gulp.task('watch', ['build'], function() {
	gulp.watch(['app/**/*.js','app/**/*.tpl.html'], {cwd:settings.srcFolder}, ['app'] );
});




function swallowError(error) {
	//If you want details of the error in the console
	console.log(gulpUtil.colors.black.bgRed('ERROR'), error.toString());
	this.emit('end');
}

/**
 * http://stackoverflow.com/a/14974931
 */
function extend(target) {
	var sources = [].slice.call(arguments, 1);
	sources.forEach(function (source) {
		for (var prop in source) {
			target[prop] = source[prop];
		}
	});
	return target;
}
