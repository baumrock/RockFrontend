## [5.4.0](https://github.com/baumrock/RockFrontend/compare/v5.3.0...v5.4.0) (2025-08-02)


### Features

* add AJAX http status codes helper class ([13c0d23](https://github.com/baumrock/RockFrontend/commit/13c0d231461d0d763e473a5663647dee0db00d14))
* add support for alfred() offset ([6f193a2](https://github.com/baumrock/RockFrontend/commit/6f193a26200ce8917baeafe2c430f8063318b083))
* add window.noJQuery flag to suppress loading jquery from ALFRED ([9a3ef6f](https://github.com/baumrock/RockFrontend/commit/9a3ef6fc200cf3e2af9f7fe9c29ac26f4a4c540d))
* allow ajax endpoints outside of pw root ([9df6cc5](https://github.com/baumrock/RockFrontend/commit/9df6cc52de3c6b5b64535bb68d656569fb2e81a4))
* allow AJAX return codes in ajax _init.php file ([caa4e9e](https://github.com/baumrock/RockFrontend/commit/caa4e9e37d7539d96c13b80b0adfaf7721d3d187))
* automatically set http response code based on ajax statuscode ([a426ede](https://github.com/baumrock/RockFrontend/commit/a426ede39b22b8847d40b3d8ad0901d2fb6a4674))
* expose PW API variables to all latte template files ([3c758df](https://github.com/baumrock/RockFrontend/commit/3c758df6347bcac50a6b4e22bfcdc5b49b15cba3))


### Bug Fixes

* add is_string check ([69b8759](https://github.com/baumrock/RockFrontend/commit/69b87593020836f8b06395427f5a3d60861f38d6))
* add missing default value ([bac9499](https://github.com/baumrock/RockFrontend/commit/bac94999c686f81a284344cdb733a2fbea2c5d45))
* error if definedVars is null ([9cf9c8b](https://github.com/baumrock/RockFrontend/commit/9cf9c8b7b5fffbbd26095089cd0ffe6746756cfc))
* getFile not checking all allowed folders ([2248591](https://github.com/baumrock/RockFrontend/commit/22485916a233b08fff73144f17c4d95a088c6927))
* make sure intCode returns int ([6775757](https://github.com/baumrock/RockFrontend/commit/677575787a06dc0c15377aa85a330a5c8dd83b2e))
* toolbar alfred issue ([7a4a234](https://github.com/baumrock/RockFrontend/commit/7a4a2349ae57bc3c0aad89c6b141006d80608dfd))

## [5.3.0](https://github.com/baumrock/RockFrontend/compare/v5.2.0...v5.3.0) (2025-07-02)


### Features

* add new grow() concept ([3d304b6](https://github.com/baumrock/RockFrontend/commit/3d304b60861704ade89855709d1a06328ae30509))
* add show/hide toggle for toolbar ([bd4d9df](https://github.com/baumrock/RockFrontend/commit/bd4d9df8fd74902b6ea867e1b748321719274391))

## [5.2.0](https://github.com/baumrock/RockFrontend/compare/v5.1.1...v5.2.0) (2025-06-01)


### Features

* add _init.php file for global ajax access control ([6bff1a0](https://github.com/baumrock/RockFrontend/commit/6bff1a0f040299817c9d2d8377f5a8ea2cab9ae0))
* expose variables from _init.php to all ajax files ([b3e85af](https://github.com/baumrock/RockFrontend/commit/b3e85afafc7bb915deae6b96e61ecc5155e54ebc))


### Bug Fixes

* add missing livereloadScriptTag method ([efe557e](https://github.com/baumrock/RockFrontend/commit/efe557ead5a76a78ffa7beb7a717402a42e6c861))
* livereload not working in ajax endpoints ([25fa46d](https://github.com/baumrock/RockFrontend/commit/25fa46d63c4c47bff43b9f821f1e08fbc923e2fa))
* throw a 404 instead of 403 if no access to ajax endpoint ([38f19a5](https://github.com/baumrock/RockFrontend/commit/38f19a597db74f96da3efdd6f5927f4ea78bdb6a))

## [5.1.1](https://github.com/baumrock/RockFrontend/compare/v5.1.0...v5.1.1) (2025-03-14)


### Bug Fixes

* issue when using range() on single day event ([7b7a7e3](https://github.com/baumrock/RockFrontend/commit/7b7a7e33b3c998e8aa16dd043a3cb9ebdac00e5e))

## [5.1.0](https://github.com/baumrock/RockFrontend/compare/v5.0.0...v5.1.0) (2025-03-01)


### Features

* add new toolbar ([f275716](https://github.com/baumrock/RockFrontend/commit/f275716d53826840846300222a2d8da2b9a3f9f1))
* add overlay toggle tool ([3fbad0e](https://github.com/baumrock/RockFrontend/commit/3fbad0efbe159ab7d973f7ca471673c04614552a))
* add persist feature for toolbar ([7be6cb4](https://github.com/baumrock/RockFrontend/commit/7be6cb40ae568a0dfe6eaa306ffe991e063d6dae))
* add styles() and scripts() with note about migration guide ([cc96871](https://github.com/baumrock/RockFrontend/commit/cc96871fe0ed6859fdd380df56fb93b31169e446))
* add support for different icons based on toggle state ([bd96e83](https://github.com/baumrock/RockFrontend/commit/bd96e8343eadc21e3f90627d5434c6a1eb60e682))
* add tailwind width info to toolbar ([e413625](https://github.com/baumrock/RockFrontend/commit/e413625238c563295af1dbd765a9263d6ff4316c))
* load tools from /site/templates ([b8efd7b](https://github.com/baumrock/RockFrontend/commit/b8efd7b1150ab4a7da86c6123964c7492e035c64))
* sort toolbar items by name ([a46761c](https://github.com/baumrock/RockFrontend/commit/a46761c85cc95d9b7cecb9c860d8f14bf9c08918))


### Bug Fixes

* improve rockdevtools check ([dc3308a](https://github.com/baumrock/RockFrontend/commit/dc3308a4ceb68159f2e356a02b174fb3fdf15ce8))
* toolbar throwing error for guest users ([e0c3c2d](https://github.com/baumrock/RockFrontend/commit/e0c3c2dd011f97728917999727beb20cc014c676))
* use filemtime for scriptTag and styleTag by default ([45df91e](https://github.com/baumrock/RockFrontend/commit/45df91e806798a7566b5c8c0f8e7cdab68083dea))

