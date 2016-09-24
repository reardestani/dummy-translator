var gulp = require('gulp');
var wpPot = require('gulp-wp-pot');
var sort = require('gulp-sort');

gulp.task('generate-pot', function () {
  return gulp.src('**/*.php')
    .pipe(sort())
    .pipe(wpPot( {
        domain: 'dummy-translator',
        destFile:'dummy-translator.pot',
        package: 'package_name',
        lastTranslator: 'John Doe <mail@example.com>',
        team: 'Team Team <mail@example.com>'
    } ))
    .pipe(gulp.dest('languages'));
});

gulp.task('default', ['generate-pot']);
