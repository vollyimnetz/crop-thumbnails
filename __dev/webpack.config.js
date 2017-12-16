const ExtractTextPlugin = require('extract-text-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

/**
 * Run with "webpack --env.prod" to build for production
 * Run with "webpack --env.dev" (default) for development
 * @param {*} env 
 */

module.exports = function (env) {
    var config = {};
    try {
        const extractLess = new ExtractTextPlugin({
            filename: "app.css",
            disable: false
        });

        config = {
            context: path.join(__dirname, 'app'),
            entry: __dirname + "/index.js",
            output: {
                path: __dirname + '/../app',
                filename: 'app.js'
            },
            resolve: {
                alias: {
                    '~': path.resolve(__dirname),//provides '~' for importing from root
                }
            },
            externals: {//excluding dependencies from the output bundles
                jquery: 'jQuery'
            },
            //devtool: setDevTool(),
            module: {
                rules: [
                    {//babel es6
                        test: /\.js$/,
                        use: 'babel-loader',
                        exclude: [
                            /node_modules/
                        ]
                    },
                    {//for inline-require of template files
                        test: /\.tpl.html/,
                        loader: 'raw-loader'
                    }, 
                    {//less-stack
                        test: /\.less$/,
                        use: extractLess.extract({
                            use: [{
                                loader: "css-loader"
                            }, {
                                loader: "autoprefixer-loader"
                            }, {
                                loader: "less-loader"
                            }],
                            // use style-loader in development
                            fallback: "style-loader"
                        })
                    }
                ]
            },
            plugins: [
                extractLess,
                
                new CopyWebpackPlugin([
                    {
                        from: __dirname + '/node_modules/vue/dist/vue.min.js',
                        to: __dirname + '/../app/vendor/vue.min.js'
                    }, {
                        from: __dirname + '/node_modules/vue/dist/vue.js',
                        to: __dirname + '/../app/vendor/vue.js'
                    }, /*{
                        from: __dirname + '/node_modules/cropperjs/dist/cropper.min.js',
                        to: __dirname + '/../app/vendor/',
                        toType: 'dir'
                    }, {
                        from: __dirname + '/node_modules/cropperjs/dist/cropper.min.css',
                        to: __dirname + '/../app/vendor/',
                        toType: 'dir'
                    }//*/
                ])
            ]
        };


        if (env && env.export) {//only if "webpack --env.export" is used
            console.log('EXPORT');
            var settings = require('./developmentSettings.js').settings;
            if (typeof settings.exportFolder !== 'string') {
                console.error('You have to define an export-folder-setting in your developmentSettings.js!');
            } else {
                config.plugins.push(
                    new CopyWebpackPlugin([
                        {
                            context: __dirname + '/..',
                            from: '.',
                            to: settings.exportFolder,
                            ignore: [
                                '.git/',
                                '.git/**/*',
                                '__dev/',
                                '__dev/**/*',//exclude the dev folder
                                'readme.md'
                            ]
                        }
                    ])
                );
            }
        }

    } catch (e) {
        console.error(e);
    }

    return config;
}