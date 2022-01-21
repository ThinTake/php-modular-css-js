const path = require('path');
const fs = require('fs');
const { exit } = require('process');

var entryMap = {};
var entryDir = path.resolve(__dirname, './src');
var dirs = fs.readdirSync(entryDir);
dirs.forEach(dir => {
    var SubDir = path.resolve(__dirname, entryDir+path.sep+dir);
    var files = fs.readdirSync(SubDir);
    files.forEach(file => {
        var fileExt = file.split('.').pop();
        if(fileExt == 'scss'){
            var name = file.replace('scss', "css");
        }
        else{
            var name = file.replace(/\.[^/.]+$/, "");
        }
        entryMap[name] = entryDir+path.sep+dir+path.sep+file;
    })
});
console.log(entryMap);

// entries.forEach(function(entry){
//     var stat = fs.statSync(entryDir + path.sep + entry);
//     if (stat && !stat.isDirectory()) {
//         var name = entry.substr(0, entry.length -1);
//         entryMap[name] = entryDir + path.sep + entry;
//     }
// });