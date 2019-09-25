const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyWebpackPlugin = require('copy-webpack-plugin');
const { VueLoaderPlugin } = require('vue-loader');
const path = require('path');

/**
 * Run with "webpack --env.prod" to build for production
 * Run with "webpack --env.dev" (default) for development
 * @param {*} env 
 */

module.exports = function (env) {
    var config = {};
    try {
        //needed to extract less
        const extractLess = new MiniCssExtractPlugin({ filename: "app.css", disable: false });

        config = {
            mode:'production',
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
                rules: [{
                        test: /\.vue$/,
                        loader: 'vue-loader'
                    }, {//babel es6
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
                        use: [
                            {
                                loader: MiniCssExtractPlugin.loader,
                            }, {
                                loader: "css-loader",
                                options: { url: false }
                            }, {
                                loader: "postcss-loader",
                                options: { 
                                    sourceMap: false,
                                    plugins: [
                                        require('postcss-discard-comments'),
                                        require('autoprefixer')
                                    ]
                                }
                            }, {
                                loader: "less-loader"
                            }
                        ]
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
                ]),
                new VueLoaderPlugin(),
            ]
        };
        config.mode = 'production';


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