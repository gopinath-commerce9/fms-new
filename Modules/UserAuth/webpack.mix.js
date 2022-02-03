const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env'/*, debug: true*/}));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

let publicMainAssets = './../../public';
let moduleAssetDir = __dirname + '/Resources/assets';
mix.setPublicPath(publicMainAssets).mergeManifest();

mix.copy(moduleAssetDir + '/js/app.js', publicMainAssets + '/js/users.js');
mix.sass(moduleAssetDir + '/sass/app.scss',  'css/users.css');

/*mix.js(__dirname + '/Resources/assets/js/app.js', 'js/userauth.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/userauth.css');*/

if (mix.inProduction()) {
    mix.version();
}
