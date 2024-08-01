## [3.19.0](https://github.com/baumrock/RockFrontend/compare/v3.18.2...v3.19.0) (2024-08-01)


### Features

* load /site/livereload.php on file change ([12105da](https://github.com/baumrock/RockFrontend/commit/12105daeda8182a29cf3768066a4610ab099bacb))
* make addLiveReload hookable ([2f3f95b](https://github.com/baumrock/RockFrontend/commit/2f3f95b66457e3b5e54b1b25a6c19ddf52081757))
* new method addPageEditWrapper() for custom page edit markup ([c80f566](https://github.com/baumrock/RockFrontend/commit/c80f566db13ba575c61b1de6682e0e5b2357e6f6))


### Bug Fixes

* issue in addPageEditWrapper when no field exists ([604349a](https://github.com/baumrock/RockFrontend/commit/604349a79a688bb3712af6080231c58fc656d928))
* remove reload on error ([d43f14f](https://github.com/baumrock/RockFrontend/commit/d43f14fcf4644750da90331ffd78164352ddc870))

## [3.18.2](https://github.com/baumrock/RockFrontend/compare/v3.18.1...v3.18.2) (2024-07-09)


### Bug Fixes

* add minified topbar css ([034f0e2](https://github.com/baumrock/RockFrontend/commit/034f0e21557cf5aa6e62ef321cf12247a754799d))
* prevent livereload from calling npm run build in the loop more than once ([4a787c4](https://github.com/baumrock/RockFrontend/commit/4a787c47f15f1dccd809e020b729380da3c4b92f))

## [3.18.1](https://github.com/baumrock/RockFrontend/compare/v3.18.0...v3.18.1) (2024-07-02)


### Bug Fixes

* disable preflight ([c4e3723](https://github.com/baumrock/RockFrontend/commit/c4e37232ca220f713991de20d672bae83e3d7608))

## [3.18.0](https://github.com/baumrock/RockFrontend/compare/v3.17.0...v3.18.0) (2024-07-01)


### Features

* add $rockfrontend->ajax flag ([280b215](https://github.com/baumrock/RockFrontend/commit/280b21538e5be7e99a7c9225881b080af3592f56))
* dont trigger alfred modal on double clicks on links and buttons ([f217bbe](https://github.com/baumrock/RockFrontend/commit/f217bbe80e9030c05fce937cc142f73cf04c5a22))
* make loadTwig hookable ([9343d21](https://github.com/baumrock/RockFrontend/commit/9343d21f7d3dec32f3bfe16e0463d836f862759d))


### Bug Fixes

* error when $refs null due to network problems ([e75f9cb](https://github.com/baumrock/RockFrontend/commit/e75f9cb3718f5840c3962d854703e6a201920eae))

## [3.17.0](https://github.com/baumrock/RockFrontend/compare/v3.16.2...v3.17.0) (2024-06-09)


### Features

* add support for 3rd party ajax endpoints ([47302a0](https://github.com/baumrock/RockFrontend/commit/47302a0f2122dabaeda4defc1625a0b16c6992af))

