## [5.5.0](https://github.com/baumrock/RockFrontend/compare/v5.4.0...v5.5.0) (2025-09-01)


### Features

* allow custom editpage for toolbar ([b511da2](https://github.com/baumrock/RockFrontend/commit/b511da2698bbf26fc1eb5003a95905cdb016b87c))

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

