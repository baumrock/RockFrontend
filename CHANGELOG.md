## [3.2.1](https://github.com/baumrock/RockFrontend/compare/v3.2.0...v3.2.1) (2023-07-04)


### Bug Fixes

* typo ([5981247](https://github.com/baumrock/RockFrontend/commit/59812470372926658adc895ea6c0a519a5e0654e))



# [3.2.0](https://github.com/baumrock/RockFrontend/compare/v3.1.0...v3.2.0) (2023-07-04)


### Bug Fixes

* RockFrontend script tag in markup when not needed ([25364be](https://github.com/baumrock/RockFrontend/commit/25364bec85a3c24e74127304e4131ee0e824bb5e))


### Features

* add deprecation note for noAssets ([a5a200f](https://github.com/baumrock/RockFrontend/commit/a5a200fbf69eeae09e04e431fef9b75f0434281c))
* add HumanDates ([e5cb3d9](https://github.com/baumrock/RockFrontend/commit/e5cb3d9e7d0212d88f5e92dbfb759bc2d91dcff7))
* add lattepanel again ([82957c0](https://github.com/baumrock/RockFrontend/commit/82957c08621529452fda5fe1e6045733ee193507))
* don't load livereload in iframes and update minification ([75e6699](https://github.com/baumrock/RockFrontend/commit/75e66994813946ce943913b768ee640e9188528c))
* only load RockFrontend.js if enabled ([f62ec7e](https://github.com/baumrock/RockFrontend/commit/f62ec7e4790dbb13a913c4bce1ca52ea72a89319))



# [3.1.0](https://github.com/baumrock/RockFrontend/compare/v3.0.1...v3.1.0) (2023-06-07)


### Bug Fixes

* empty string leads to empty filename ([f929762](https://github.com/baumrock/RockFrontend/commit/f9297625df6b6ef9353af01d8965dfb1742b25c9))
* prevent double adding of assets ([14b9b10](https://github.com/baumrock/RockFrontend/commit/14b9b10e01c731a172f2c0532dcc6b9d7adebc71))
* remove lattepanel causing compile error ([119e97c](https://github.com/baumrock/RockFrontend/commit/119e97c00db37cfa4c6d18a901bb67eee4b89e9e))


### Features

* rename default asset name from head to main ([fc3d1ae](https://github.com/baumrock/RockFrontend/commit/fc3d1ae36795c09cb74f08db585dfb8579d95b3b))



## [3.0.1](https://github.com/baumrock/RockFrontend/compare/v3.0.0...v3.0.1) (2023-06-02)


### Bug Fixes

* remove unused options reference ([7ad201e](https://github.com/baumrock/RockFrontend/commit/7ad201eb99ea91085557f7fdf124dc744333c010))



# [3.0.0](https://github.com/baumrock/RockFrontend/compare/v2.40.0...v3.0.0) (2023-06-01)


### Bug Fixes

* script loaded twice when using addAll() and minify() ([fbe21b1](https://github.com/baumrock/RockFrontend/commit/fbe21b1439d8e45b2586155dcb1147ed93e6e81d))
* typecast string in getChangedFiles ([3b5d43a](https://github.com/baumrock/RockFrontend/commit/3b5d43a02f7ecbea6309643f38144c020246683c))


* feat!: new concept of autoload assets ([527d4fa](https://github.com/baumrock/RockFrontend/commit/527d4fa5e1b31b866703a05a80eabf6abe4ee2d0))


### Features

* add .no-alfred class to prevent doubleclick popup ([af40517](https://github.com/baumrock/RockFrontend/commit/af40517ef27cd0f878b4bcc4e6902f9e466e0fb2))
* add dedicated method loadLatte() ([b0d1ac6](https://github.com/baumrock/RockFrontend/commit/b0d1ac6b24bdf5085272d4c324db1b1f0e58fcd6))
* add dom() method ([a564290](https://github.com/baumrock/RockFrontend/commit/a564290780c226ebee4841f3211d863ec487fde8))
* add livereload to debug info ([87bb33d](https://github.com/baumrock/RockFrontend/commit/87bb33d743aca46ea9b323d09fb22298c1c0e699))
* add RockFrontend.debounce() ([a6f1af0](https://github.com/baumrock/RockFrontend/commit/a6f1af0017840803e057c21253fd225876371896))
* add support for topbar prepend/append markup ([c33665c](https://github.com/baumrock/RockFrontend/commit/c33665c552b9e928ec3e3f6c41dc33280cc80d39))
* add topbar toggle ([f9bf247](https://github.com/baumrock/RockFrontend/commit/f9bf247ef526fb726518377f726670b65127776a))
* auto-update htaccess to block access to latte/twig/blade/less files ([229277c](https://github.com/baumrock/RockFrontend/commit/229277cb09d8b89a68e31793f02133efc1d4f790))
* hide url+user in livereload logs ([3e25a01](https://github.com/baumrock/RockFrontend/commit/3e25a01fd55cee4bfc568f12065125e5a67dc9a8))
* update vendor and add HtmlPageDom ([a705851](https://github.com/baumrock/RockFrontend/commit/a70585152dad9eb67a90f1f6298f3c2ab66e0a7c))


### BREAKING CHANGES

* This might introduce a breaking change in existing sites. Also improves logging.



