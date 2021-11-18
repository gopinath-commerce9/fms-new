const dotenvExpand = require('dotenv-expand');
dotenvExpand(require('dotenv').config({ path: '../../.env'/*, debug: true*/}));

const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

let publicMainAssets = './../../public';
let moduleAssetDir = __dirname + '/Resources/assets';
mix.setPublicPath(publicMainAssets).mergeManifest();

mix.copy(moduleAssetDir + '/js/app.js', publicMainAssets + '/js/userrole.js');
mix.copy(moduleAssetDir + '/js/permissions-app.js', publicMainAssets + '/js/permission.js');
mix.copy(moduleAssetDir + '/js/pickers-app.js', publicMainAssets + '/js/role-pickers.js');
mix.copy(moduleAssetDir + '/js/drivers-app.js', publicMainAssets + '/js/role-drivers.js');
mix.sass(moduleAssetDir + '/sass/app.scss',  'css/userrole.css');

/*mix.js(__dirname + '/Resources/assets/js/app.js', 'js/userrole.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/userrole.css');*/

if (mix.inProduction()) {
    mix.version();
}
