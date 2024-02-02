## [3.11.0](https://github.com/baumrock/RockFrontend/compare/v3.10.0...v3.11.0) (2024-02-02)


### Features

* add option to trigger "npm run build" on changed file ([85079f0](https://github.com/baumrock/RockFrontend/commit/85079f029a3c220146487d459f4023a64710b56f))

## [3.10.0](https://github.com/baumrock/RockFrontend/compare/v3.9.0...v3.10.0) (2024-02-02)


### Features

* add lighten() and darken() methods for hex values ([852ea50](https://github.com/baumrock/RockFrontend/commit/852ea5008d8220e1724aacf54af05e8888f8237a))
* add once() helper ([0938e98](https://github.com/baumrock/RockFrontend/commit/0938e98c84f88b7ff585f8d89494edabeafc53a0))
* add sortable toggle to topbar ([f08e14d](https://github.com/baumrock/RockFrontend/commit/f08e14d39d3c55b9a3728e46dbb90d9966638b39))
* improve consent tools, add "has-consent-click" and "needs-consent-click" ([fdfa07f](https://github.com/baumrock/RockFrontend/commit/fdfa07fe31afba74ce9e456afe8742761b5c2270))
* improve topbar ([27f20d3](https://github.com/baumrock/RockFrontend/commit/27f20d3ab00ab7b6a8fd80d35921551fe8bd5179))
* improve topbar toggle ([d6f5c76](https://github.com/baumrock/RockFrontend/commit/d6f5c762d5a23666227c893d0ff06360f5431f76))
* improve ui of sortable toggle ([332fd0e](https://github.com/baumrock/RockFrontend/commit/332fd0e65fc1438cded81a2842dc2173f50bf2a0))
* make link handler for alfred links configurable ([89ae18c](https://github.com/baumrock/RockFrontend/commit/89ae18c67dbc44cd89a46ddf2458627518e1d836))
* make loadLatte hookable ([c3e4f47](https://github.com/baumrock/RockFrontend/commit/c3e4f47196d5d5f86759a503a418211a370ff7c7))
* make svg() work with pagefiles ([c21ea10](https://github.com/baumrock/RockFrontend/commit/c21ea10f72fda25588504b2c9b4b60a5f265aa3a))
* refactor topbar ([79a688f](https://github.com/baumrock/RockFrontend/commit/79a688ff6907193c9072858ee4f099c452f0cf07))
* remove homepage field migrations ([9853553](https://github.com/baumrock/RockFrontend/commit/9853553bbb5a24cdb0fb6563d5a62d39280f48ed))
* toggle alfred ui on CMD/CTRL ([f23fb4b](https://github.com/baumrock/RockFrontend/commit/f23fb4ba7b1a70fab54b68a291454c059b00b804))
* update profile to use layout.latte ([15ad6cf](https://github.com/baumrock/RockFrontend/commit/15ad6cfa032c0b37c6e6db10d608f8d80d975b2e))


### Bug Fixes

* autoprepend throwing errors, fix [#21](https://github.com/baumrock/RockFrontend/issues/21) ([594dd2b](https://github.com/baumrock/RockFrontend/commit/594dd2b1090519cc43f585919e7d47d9c472f381))
* don't create manifest by default ([1b17b86](https://github.com/baumrock/RockFrontend/commit/1b17b862c34133fb67a29373ccf1e82198a917d3))
* fix debugInfo throwing error if folders are null ([c69a79e](https://github.com/baumrock/RockFrontend/commit/c69a79e382d51b2bdc3697886a6a35fc6567aabb))
* prevent loading AutoPrepend on non-templatefile render() calls ([0eeb184](https://github.com/baumrock/RockFrontend/commit/0eeb1840e2eade6f27e9ebbfb4a378b7374324e4))

## [3.9.0](https://github.com/baumrock/RockFrontend/compare/v3.8.2...v3.9.0) (2024-01-03)


### Features

* add humandates via composer ([eabbe8b](https://github.com/baumrock/RockFrontend/commit/eabbe8b2b4d9ece590ec5a30ae6b26f9afe1986c))
* add ogImage shortcut ([2131298](https://github.com/baumrock/RockFrontend/commit/2131298635b09fcefed49fbbbdc1ba4def265a28))
* add rockfrontend() functions api ([4991da5](https://github.com/baumrock/RockFrontend/commit/4991da52481f11d79df9e90a7635b03f7ad64e87))
* add setViewFolders to support RockCommerce HTMX ([dc33123](https://github.com/baumrock/RockFrontend/commit/dc3312329ba620a3008e77fd2117b726c9efbe2a))
* add variables array to view() method ([c98ee66](https://github.com/baumrock/RockFrontend/commit/c98ee667712de44bd697fade8a93dc0a40042a81))
* improve view() method for rockcommerce ([c05576a](https://github.com/baumrock/RockFrontend/commit/c05576a3393c277d4d143938e0ac519e6df4ec6a))
* update dependencies (requires PHP8.1) ([488e689](https://github.com/baumrock/RockFrontend/commit/488e68937c5356504b39adbebe461466c077b306))
* update required php version in info ([529c113](https://github.com/baumrock/RockFrontend/commit/529c113eb36a2c3a47e955d5f68c1264f751811c))
* update to support new rockpagebuilder sortable handles ([80099f5](https://github.com/baumrock/RockFrontend/commit/80099f5770803bff1ff5eee82adba769670a6f83))


### Bug Fixes

* fix livereload issue on PHP8.2 ([7660ea4](https://github.com/baumrock/RockFrontend/commit/7660ea423a6a7ac9d315f61d2c4d682f65f136c5))

## [3.8.2](https://github.com/baumrock/RockFrontend/compare/v3.8.1...v3.8.2) (2023-12-04)


### Bug Fixes

* don't load layout file on RockPdf rendering ([fd6eaa3](https://github.com/baumrock/RockFrontend/commit/fd6eaa33600d8435354e2c1055685349d4b5132a))

## [3.8.1](https://github.com/baumrock/RockFrontend/compare/v3.8.0...v3.8.1) (2023-12-04)


### Bug Fixes

* normalize windows file path ([7895e06](https://github.com/baumrock/RockFrontend/commit/7895e06254329b333d5ac4a38110cbb1b3804209))

