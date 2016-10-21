# [<img alt="Vanio" src="http://www.vanio.cz/img/vanio-logo.png" width="130" align="top">](http://www.vanio.cz) Domain Bundle

[![Build Status](https://travis-ci.org/vaniocz/vanio-domain-bundle.svg?branch=master)](https://travis-ci.org/vaniocz/vanio-domain-bundle)
[![Coverage Status](https://coveralls.io/repos/github/vaniocz/vanio-domain-bundle/badge.svg?branch=master)](https://coveralls.io/github/vaniocz/vanio-domain-bundle?branch=master)
![PHP7](https://img.shields.io/badge/php-7-6B7EB9.svg)
[![License](https://poser.pugx.org/vanio/vanio-domain-bundle/license)](https://github.com/vaniocz/vanio-domain-bundle/blob/master/LICENSE)

# Installation
Installation can be done as usually using composer.
`composer require vanio/vanio-domain-bundle`

Next step is to register this bundle as well as bundles it depends on inside your `AppKernel`.
```php
// app/AppKernel.php
// ...

class AppKernel extends Kernel
{
    // ...

    public function registerBundles(): array
    {
        $bundles = [
            // ...
            new Vanio\UserBundle\VanioDomainBundle,
        ];

        // ...
    }
}
```
