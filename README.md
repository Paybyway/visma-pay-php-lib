Visma Pay PHP Library
=

See documentation at https://www.vismapay.com/docs/web_payments/

The library can be installed using composer.

     composer require visma-pay/visma-pay

To install with composer, add following to composer.json and run ** composer update **
    
    {
      "require": {
        "visma-pay/visma-pay": "^1.0.0"
      }
    }

For manual installation, you need to use provided loader

    require './path-to-rest-php-lib/lib/visma_pay_loader.php';
