## [3.15.1](https://github.com/baumrock/RockFrontend/compare/v3.15.0...v3.15.1) (2024-05-12)


### Bug Fixes

* sitemap error on multi-language [#28](https://github.com/baumrock/RockFrontend/issues/28) ([34b2b96](https://github.com/baumrock/RockFrontend/commit/34b2b96d9df89544503543905ebf2fa8b1666403))

## [3.15.0](https://github.com/baumrock/RockFrontend/compare/v3.14.0...v3.15.0) (2024-05-08)


### Features

* catch minify error ([8404bc3](https://github.com/baumrock/RockFrontend/commit/8404bc3bbe0169cafcaf2ecb1414dee4a665c8ed))


### Bug Fixes

* rename liveReloadForce to livereloadForce ([ee17e88](https://github.com/baumrock/RockFrontend/commit/ee17e880cb9165e38e1f3c1b27a091eb0022ff7b))

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

