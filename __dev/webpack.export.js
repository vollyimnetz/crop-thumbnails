const CopyWebpackPlugin = require('copy-webpack-plugin');
var developmentSettings = require('./developmentSettings.js').settings;

module.exports = function (env) {
  var config = {

  };
  try {
    console.log('EXPORT',developmentSettings);
    if (typeof developmentSettings.exportFolder !== 'string') {
      console.error('You have to define an export-folder-setting in your developmentSettings.js!');
    } else {
      config.plugins = [
        new CopyWebpackPlugin({
          patterns: [
            {
              context: __dirname + '/..',
              from: '.',
              to: developmentSettings.exportFolder,
              globOptions: {
                dot: true,
                gitignore: true,
                ignore: [
                  '.git/',
                  '.git/**/*',
                  '__dev/',
                  '__dev/**/*',//exclude the dev folder
                  'readme.md'
                ],
              },
            }
          ]
        })
      ];
    }
  } catch (e) {
    console.error(e);
  }

  return config;
}