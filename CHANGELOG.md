## [3.22.1](https://github.com/baumrock/RockFrontend/compare/v3.22.0...v3.22.1) (2024-10-21)


### Bug Fixes

* favicon.ico status detection ([9c73e13](https://github.com/baumrock/RockFrontend/commit/9c73e13e3aaef28e6978236c1fa5272605728e66))

## [3.22.0](https://github.com/baumrock/RockFrontend/compare/v3.21.2...v3.22.0) (2024-10-20)


### Features

* add int as return type for field() method ([bcd403c](https://github.com/baumrock/RockFrontend/commit/bcd403c7b867945852ea987759d57c5309d635a6))
* catch errors in public ajax endpoint only if debug=false (for better debugging) ([cfffdb5](https://github.com/baumrock/RockFrontend/commit/cfffdb53eba298e9739ccffd83b57cce84451d3d))
* improve consent tools to work in ajax loaded modals ([119ea86](https://github.com/baumrock/RockFrontend/commit/119ea866d53c27848545c9fd9046cf870319e332))
* make PW functions available to latte files ([e02091b](https://github.com/baumrock/RockFrontend/commit/e02091bacdd0dd167b9b790d58ae5f28f2149f32))


### Bug Fixes

* livereload warning showing up every second ([aee82a6](https://github.com/baumrock/RockFrontend/commit/aee82a6af05112644dee44f4ea71c60d4c48c33e))
* load composer autoloader in init() ([4987feb](https://github.com/baumrock/RockFrontend/commit/4987febddaa843002aeffc7db1b19bdd73beb8b2))

## [3.21.2](https://github.com/baumrock/RockFrontend/compare/v3.21.1...v3.21.2) (2024-10-02)


### Bug Fixes

* latte files not working in ajax endpoints ([3e6e8a1](https://github.com/baumrock/RockFrontend/commit/3e6e8a13edbc98e068bc470370e8f0e938fbb2b5))

## [3.21.1](https://github.com/baumrock/RockFrontend/compare/v3.21.0...v3.21.1) (2024-09-30)


### Bug Fixes

* toPath() issue on subfolder installations ([911dd3b](https://github.com/baumrock/RockFrontend/commit/911dd3bfb79566ae5f4dbfc03febe28bd89c0a39))

## [3.21.0](https://github.com/baumrock/RockFrontend/compare/v3.20.0...v3.21.0) (2024-09-30)


### Features

* improve ajax features and docs ([5a04285](https://github.com/baumrock/RockFrontend/commit/5a0428526f4776e873d7c03b7a3d52bbae6ece19))
* support nested ajax endpoint folders ([064e37c](https://github.com/baumrock/RockFrontend/commit/064e37c0bfd4f9ae8377ae122e1b5426b18d52ee))


### Bug Fixes

* remove pages->get() call in livereload ([fda2deb](https://github.com/baumrock/RockFrontend/commit/fda2deb934c7ab022401a6d75bbff09ce8d367f3))
* remove try/catch when debug mode is on to show tracy bluescreen for latte files ([8b8f4df](https://github.com/baumrock/RockFrontend/commit/8b8f4dffa5bd26cbc3d48c10434ce9c0aba6b517))
* wrong return types in new field() method ([8b5b7af](https://github.com/baumrock/RockFrontend/commit/8b5b7afc46650d99a9736fc51ce9aa6a3faccdd4))

