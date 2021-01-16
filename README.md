## Laravel Wrapper for [Internetmarke and ProdWS](https://www.deutschepost.de/de/i/internetmarke-porto-drucken/downloads.html)

### Basic Usage

##### Login and get balance
```php
echo Internetmarke::login('email', 'password')
    ->getBalance()
// 1234.56
```

##### Preview stamp [PNG]
```php
$preview_url = \Internetmarke::login('email', 'password')
    ->getPreviewPNG(1, 'AddressZone');
// https://internetmarke.deutschepost.de/PcfExtensionWeb/preview?keyphase=0&data=...
```

##### Purchase stamp [PNG]
```php
$receiver = Internetmarke::createAddress()
    ->company('Testfirma GmbH')
    ->street('Musterstrasse')
    ->housenr('1')
    ->zipcode('12345')
    ->city('Musterstadt')
    ->country('DEU'); // ISO-3166-1 Alpha-3 code

$sender = Internetmarke::createAddress()
    ->firstname('Max')
    ->lastname('Mustermann')
    ->streetAndHousenr('Musterstrasse 1')
    ->zipcodeAndCity('12345 Musterstadt')
    ->country('DEU'); // ISO-3166-1 Alpha-3 code

$full_path_to_png = Internetmarke::login('email', 'password')
    ->checkoutPDF(null, 1, 80, 'AddressZone', $receiver, $sender)
    ->savePDF();
```

##### Retrieve products (ProdWS)
```php
$only_sales_products = false; // false/true, default: false
$products = ProdWS::getProducts($only_sales_products);
/**
 * Example response (json encoded):
 * {
 *    "sales_products":[
 *       {
 *          "extendedIdentifier":{ ... },
 *          "priceDefinition":{ ... },
 *          "dimensionList":{ ... },
 *          "weight":{ ... },
 *          "destinationArea":{ ... },
 *          "categoryList":{ ... },
 *          "stampTypeList":{ ... },
 *          "accountProductReferenceList":{ ... }
 *       },
 *       [...]
 *    ],
 *    "basic_products":[
 *       {
 *          "extendedIdentifier":{ ... },
 *          "priceDefinition":{ ... },
 *          "dimensionList":{ ... },
 *          "weight":{ ... },
 *          "propertyList":{ ... },
 *          "destinationArea":{ ... },
 *          "documentReferenceList":{ ... }
 *       },
 * 	     [...]
 *    ],
 *    "additional_products":[
 *       {
 *          "extendedIdentifier":{ ... },
 *          "priceDefinition": { ... },
 *          "propertyList": { ... },
 *          "documentReferenceList": { ... }
 *       },
 *       [...]
 *    ]
 * }
 */
```

### Configuration

You can either set the following environment variables:

    INTERNETMARKE_PARTNER_ID=""
    INTERNETMARKE_SECRET_KEY=""
    INTERNETMARKE_KEY_PHASE=1
    PRODWS_MANDANT_ID=""
    PRODWS_USERNAME=""
    PRODWS_PASSWORD=""

or publish and edit the `internetmarke.php` configuration file:

    php artisan vendor:publish --tag=internetmarke-config


### Glossary

##### Portokasse

Portokasse is a prepaid wallet account system, with which you can top up your balance and purchase internet stamps.

##### ProdWS

ProdWS is a soap web service for retrieving the currently valid `product- and price list (PPL)`.

There are **sales**, **basic** and **additional** products. Sales products are the combinations of one basic product and several additional products. To purchase a stamp, you have to provide the **PPL-ID** of the desired sales product.

##### Internetmarke

Internetmarke is a soap web service for previewing and purchasing internet stamps, after authenticating with an `Portokasse` account.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](LICENSE).