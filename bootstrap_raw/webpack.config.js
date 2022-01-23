const path = require('path');
const fs = require('fs');

/**
 * START: add all files to entry
 */
// var entryMap = {};
// var entryDir = path.resolve(__dirname, './src');
// var dirs = fs.readdirSync(entryDir);
// dirs.forEach(dir => {
//     var SubDir = path.resolve(__dirname, entryDir+path.sep+dir);
//     var files = fs.readdirSync(SubDir);
//     files.forEach(file => {
//         var fileExt = file.split('.').pop();
//         if(fileExt == 'scss'){
//             var name = file.replace('scss', "css");
//         }
//         else{
//             var name = file.replace(/\.[^/.]+$/, "");
//         }
//         entryMap[name] = entryDir+path.sep+dir+path.sep+file;
//     })
// });

// CSS only
var entryMap = {};
var entryDir = path.resolve(__dirname, './src/css');
var files = fs.readdirSync(entryDir);
files.forEach(file => {
    var name = file.replace(/\.[^/.]+$/, "");
    entryMap[name] = entryDir+path.sep+file;
})
/**
 * END: add all files to entry
 */

module.exports = {
    mode: 'production', /* development */
    entry: entryMap,
    optimization: {
        minimize: false
    },
    output: {
        path: path.resolve(__dirname, 'dist'), 
        filename: 'js/[name]/[name].js',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [],
            }, {
                test: /\.scss$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'file-loader',
                        options: { outputPath: 'css/', name: '[name]/[name].css'}
                    },
                    'sass-loader'
                ]
            }
        ]
    },
};