## [3.14.0](https://github.com/baumrock/RockFrontend/compare/v3.13.1...v3.14.0) (2024-05-06)


### Features

* add canonical tag to default seo markup ([5d6de01](https://github.com/baumrock/RockFrontend/commit/5d6de0129a5d02710c2051aee8f0789ed9dd957a))
* add config setting to preserve success messages ([9270aa9](https://github.com/baumrock/RockFrontend/commit/9270aa988f4221196d0eb7793e8a563142f3ed85))
* add isDDEV property only if true ([3b997af](https://github.com/baumrock/RockFrontend/commit/3b997af19b62b1202f33846c68f89f4433696a5e))
* add livereload to ajax debug screen ([ec454d2](https://github.com/baumrock/RockFrontend/commit/ec454d2a78d3be720c154fbaaf1f3695dafa79ff))
* add livereload to tracy bluescreen ([876d421](https://github.com/baumrock/RockFrontend/commit/876d421376dfbc3e026610d7dc06cc3362937602))
* add liveReloadForce setting ([e4fda2e](https://github.com/baumrock/RockFrontend/commit/e4fda2e6efb3104945a89ca0976e8e4bff889154))
* add multilang support for sitemap ([71c6e41](https://github.com/baumrock/RockFrontend/commit/71c6e414324c3fab3af6e856b8be087463c5244a))
* add warning that ALFRED needs frontend module ([971ef4a](https://github.com/baumrock/RockFrontend/commit/971ef4a7a31c6d96c8dab4f1c8393fba4bd2fd36))
* block direct access of ajax endpoints for guest users ([3c14dcd](https://github.com/baumrock/RockFrontend/commit/3c14dcd7361535817015c8187e8ce067e8e9d363))
* copy layout file on module screen without refresh ([1fe297b](https://github.com/baumrock/RockFrontend/commit/1fe297bc87e304946f47babdd79e8dda657bcf59))
* improve ajax endpoints as of issue [#26](https://github.com/baumrock/RockFrontend/issues/26) ([865d2ef](https://github.com/baumrock/RockFrontend/commit/865d2ef5409f9de01e321d1dc599512a3494cf0c))
* improve sitemap tools ([154b76c](https://github.com/baumrock/RockFrontend/commit/154b76c956421ef94af8422a04b4804e7725e139))
* make template file variables available in layout files ([7e01bb1](https://github.com/baumrock/RockFrontend/commit/7e01bb1cb2ad10f4a72c0b7855b4bdc9404f6269))
* support relative urls in svgDom() method ([38b9c67](https://github.com/baumrock/RockFrontend/commit/38b9c67574e4a4d41d53c0567f576d49d7bf88fd))
* upgrade dependencies ([ac2dc3e](https://github.com/baumrock/RockFrontend/commit/ac2dc3e851880d59a016f476f6668d1982392610))


### Bug Fixes

* load styles before scripts ([70de35f](https://github.com/baumrock/RockFrontend/commit/70de35f4e058558038940de1b2dd0ce957bbeed4))

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

