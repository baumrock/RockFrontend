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

## [5.0.0](https://github.com/baumrock/RockFrontend/compare/v4.1.0...v5.0.0) (2025-02-02)


### ⚠ BREAKING CHANGES

* remove styles() and scripts() features in favor of RockDevTools

### Features

* add assets() method to include assets in frontend ([4efee08](https://github.com/baumrock/RockFrontend/commit/4efee084a17ba32e745b79b9147fedd978c5db25))
* add scriptTag() and styleTag() ([b4ed1e4](https://github.com/baumrock/RockFrontend/commit/b4ed1e466d94bb19994b57033ce675080fa130da))
* load alfred overrides from AdminStyleRock ([8c14d7e](https://github.com/baumrock/RockFrontend/commit/8c14d7ee2408eaa7c60e8004c3eb7b5bc5b3e46b))
* remove styles() and scripts() features in favor of RockDevTools ([6c7ae81](https://github.com/baumrock/RockFrontend/commit/6c7ae81cc10da4c7845f9612fae66b01f05c023c))


### Bug Fixes

* reading tracy editor from env leads to problems ([34c8a79](https://github.com/baumrock/RockFrontend/commit/34c8a7900a2754cd869e567c405031b722b9f77e))
* remove default defer attribute ([505b462](https://github.com/baumrock/RockFrontend/commit/505b46267eecddd5303a1daa8cc461ab433673fd))
* remove minify() for RockFrontend.js ([6a1a21b](https://github.com/baumrock/RockFrontend/commit/6a1a21b0c8876ecf45a06240f7ffafc436976b2b))

## [4.1.0](https://github.com/baumrock/RockFrontend/compare/v4.0.0...v4.1.0) (2025-01-19)


### Features

* update latte library to latest version ([d3ac96e](https://github.com/baumrock/RockFrontend/commit/d3ac96e19f902211950715cca629d64402813648))

## [4.0.0](https://github.com/baumrock/RockFrontend/compare/v3.24.0...v4.0.0) (2024-11-30)


### ⚠ BREAKING CHANGES

* make autoPrepend optional by default and only load it if $config->rockfrontendAutoPrepend is set

### Miscellaneous Chores

* make autoPrepend optional by default and only load it if $config->rockfrontendAutoPrepend is set ([fa199cb](https://github.com/baumrock/RockFrontend/commit/fa199cb766d3cc652e4b0654473445c791b0926e))

