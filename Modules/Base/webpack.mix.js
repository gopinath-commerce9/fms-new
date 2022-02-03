const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env'/*, debug: true*/}));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

let publicMainAssets = './../../public';
let moduleAssetDir = __dirname + '/Resources/assets';
mix.setPublicPath(publicMainAssets).mergeManifest();

mix.copyDirectory(moduleAssetDir + '/backend', publicMainAssets + '/backend');
mix.copyDirectory(moduleAssetDir + '/frontend', publicMainAssets + '/frontend');
mix.copyDirectory(moduleAssetDir + '/ktmt', publicMainAssets + '/ktmt');

mix.copy(moduleAssetDir + '/js/app.js', publicMainAssets + '/js/base.js');
mix.sass(moduleAssetDir + '/sass/app.scss',  'css/base.css');

/*mix.js(moduleAssetDir + '/js/app.js', 'js/base.js')
    .sass( moduleAssetDir + '/sass/app.scss', 'css/base.css');*/

if (mix.inProduction()) {
    mix.version();
}
