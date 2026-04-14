module.exports = function(grunt) {

    'use strict';

    grunt.initConfig({

        uglify: {
            options: {
                mangle: false,
                compress: {
                    dead_code: true,
                    drop_console: false
                },
                output: {
                    comments: /^!/
                },
                sourceMap: true,
                sourceMapName: function(dest) {
                    return dest + '.map';
                }
            },
            dashboard: {
                src: 'amd/src/dashboard.js',
                dest: 'amd/build/dashboard.min.js'
            },
            charts: {
                src: 'amd/src/charts.js',
                dest: 'amd/build/charts.min.js'
            },
            export: {
                src: 'amd/src/export.js',
                dest: 'amd/build/export.min.js'
            }
        },

        watch: {
            scripts: {
                files: ['amd/src/*.js'],
                tasks: ['uglify'],
                options: {
                    spawn: false,
                    livereload: true
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('build', ['uglify']);
    grunt.registerTask('default', ['build']);
};
