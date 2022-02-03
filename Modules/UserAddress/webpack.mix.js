const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env'/*, debug: true*/}));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

let publicMainAssets = './../../public';
let moduleAssetDir = __dirname + '/Resources/assets';
mix.setPublicPath(publicMainAssets).mergeManifest();

mix.copy(moduleAssetDir + '/js/app.js', publicMainAssets + '/js/useraddress.js');
mix.sass(moduleAssetDir + '/sass/app.scss',  'css/useraddress.css');

/*mix.js(__dirname + '/Resources/assets/js/app.js', 'js/useraddress.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/useraddress.css');*/

if (mix.inProduction()) {
    mix.version();
}
