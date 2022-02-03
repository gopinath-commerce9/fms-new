const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env'/*, debug: true*/}));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

let publicMainAssets = './../../public';
let moduleAssetDir = __dirname + '/Resources/assets';
mix.setPublicPath(publicMainAssets).mergeManifest();

mix.copy(moduleAssetDir + '/js/app.js', publicMainAssets + '/js/configuration.js');
mix.sass(moduleAssetDir + '/sass/app.scss',  'css/configuration.css');

/*mix.js(__dirname + '/Resources/assets/js/app.js', 'js/configuration.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/configuration.css');*/

if (mix.inProduction()) {
    mix.version();
}
