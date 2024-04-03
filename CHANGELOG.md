## [3.13.1](https://github.com/baumrock/RockFrontend/compare/v3.13.0...v3.13.1) (2024-04-03)


### Bug Fixes

* hide topbar on formbuilder iframes ([168c223](https://github.com/baumrock/RockFrontend/commit/168c223bb7fbe49a90c7a2faf78fbf1e7df56738))

## [3.13.0](https://github.com/baumrock/RockFrontend/compare/v3.12.0...v3.13.0) (2024-04-02)


### Features

* add basic seo checks on config screen ([4d26c73](https://github.com/baumrock/RockFrontend/commit/4d26c730ef65d2cfb0bb893b80b5f24031c24d10))
* add check for favicon ([203c833](https://github.com/baumrock/RockFrontend/commit/203c83379286080079416b6d59785274bdfcd20a))
* add debug info for sitemap.xml generation ([e030f26](https://github.com/baumrock/RockFrontend/commit/e030f26fe00b78f69e045ee6162c4ef5fe7b8a0d))
* add ogimage minify warning ([1bbfd4a](https://github.com/baumrock/RockFrontend/commit/1bbfd4a4e65cdf1249a61c0b90c0dcfcadf13d94))
* add sitemap() method 😍 ([600bb32](https://github.com/baumrock/RockFrontend/commit/600bb320939371c1e678e09719431478feffbddf))
* add tailwind config installation option ([0edcc00](https://github.com/baumrock/RockFrontend/commit/0edcc00951ceab918f22a40b4d5949a2eed34630))
* detect htmx requests ([93a6181](https://github.com/baumrock/RockFrontend/commit/93a6181195a1973964391058fba5c481d692b073))
* improve asset minification features ([d3a07ba](https://github.com/baumrock/RockFrontend/commit/d3a07ba18816f11cb82c049d05133f4d08e313f0))
* load layout.less if it exists ([c8f2876](https://github.com/baumrock/RockFrontend/commit/c8f2876892d2ca037dddf71b7b8da1841ee0bcde))


### Bug Fixes

* remove migrations from module config to prevent auto-install of RockMigrations ([614dd46](https://github.com/baumrock/RockFrontend/commit/614dd46868944318ee8cb92052c131697528502c))

## [3.12.0](https://github.com/baumrock/RockFrontend/compare/v3.11.0...v3.12.0) (2024-03-12)


### Features

* add experimental support for ajax endpoints ([7db7970](https://github.com/baumrock/RockFrontend/commit/7db7970337462376d13f2e6b13f89af05740ee4b))
* add latte filters vurl + euro + euroAT ([2550732](https://github.com/baumrock/RockFrontend/commit/25507328413316af7d8799e3302f1ab7d6fc619c))
* add svgDom() method ([afdef7e](https://github.com/baumrock/RockFrontend/commit/afdef7e933f740bab827ed9554b421b75551da83))
* allow .no-alfred class on non-body elements ([df436e4](https://github.com/baumrock/RockFrontend/commit/df436e4f8d103c8bd6a0b154cb643789e2000688))
* improve livereload ([fa67da2](https://github.com/baumrock/RockFrontend/commit/fa67da2d225db12e9d7c4ecd9798e8f1e8cffcb6))
* increase z-index for alfred icons ([7ead197](https://github.com/baumrock/RockFrontend/commit/7ead1971b6c4f48a996bf4ce6b6fc0f2ae28162f))
* support env vars for editorLink() ([43ed1de](https://github.com/baumrock/RockFrontend/commit/43ed1de1b8267228385eae4252d1c669133e1242))


### Bug Fixes

* livereload tag added when livereload was disabled ([1818083](https://github.com/baumrock/RockFrontend/commit/181808339351bc1b027c1ef37b6f02678f5a1686))
* remove unused old script ([8c499d2](https://github.com/baumrock/RockFrontend/commit/8c499d2fd4a00379767c2516c7cfdc4f5b498230))
* show rockmigrations outdated warning ([a269e26](https://github.com/baumrock/RockFrontend/commit/a269e2677ef60ff5d9c8c84b08ebc6696dcf47de))

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

