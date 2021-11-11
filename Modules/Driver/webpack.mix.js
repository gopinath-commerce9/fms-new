const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env'/*, debug: true*/}));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

let publicMainAssets = './../../public';
let moduleAssetDir = __dirname + '/Resources/assets';
mix.setPublicPath(publicMainAssets).mergeManifest();

mix.copy(moduleAssetDir + '/js/app.js', publicMainAssets + '/js/driver.js');
mix.sass(moduleAssetDir + '/sass/app.scss',  'css/driver.css');

/*mix.js(__dirname + '/Resources/assets/js/app.js', 'js/driver.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/driver.css');*/

if (mix.inProduction()) {
    mix.version();
}
