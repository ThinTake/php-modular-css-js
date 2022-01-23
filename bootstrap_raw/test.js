const path = require('path');
const fs = require('fs');
const { exit } = require('process');

var entryMap = {};
var entryDir = path.resolve(__dirname, './src/css');
var files = fs.readdirSync(entryDir);
files.forEach(file => {
    var name = file.replace(/\.[^/.]+$/, "");
    entryMap[name] = entryDir+path.sep+file;
})
console.log(entryMap);

// entries.forEach(function(entry){
//     var stat = fs.statSync(entryDir + path.sep + entry);
//     if (stat && !stat.isDirectory()) {
//         var name = entry.substr(0, entry.length -1);
//         entryMap[name] = entryDir + path.sep + entry;
//     }
// });