var path = require('path');
var copydir = require('copy-dir');
var developmentSettings = require('./developmentSettings.js').settings;

if (typeof developmentSettings.exportFolder !== 'string') {
  console.error('You have to define an export-folder-setting in your developmentSettings.js!');
  exit();
}
 
copydir.sync(__dirname + '/..', developmentSettings.exportFolder, {
  filter: function(stat, filepath, filename){
    // do not want copy readme.md files
    if(stat === 'file' && filename === 'readme.md') {
      return false;
    }
    // do not want copy .svn directories
    if (stat === 'directory' && (filename === '.git' || filename==='__dev')) {
      return false;
    }
    return true;  // remind to return a true value when file check passed.
  }
});
console.log('done');