JsonPath
========
[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/Peekmo/jsonpath/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

JsonPath utility (XPath for JSON) for PHP based on **Stefan Goessner's** implementation : http://code.google.com/p/jsonpath/

C php extension is in progress [here](https://github.com/Peekmo/php-ext-jsonpath), any help is welcome :)

## Documentation ##

What is JsonPath ? What is the syntax ? Take a look to [Stefan Goessner's documentation](http://goessner.net/articles/JsonPath/)

## Installation ##

- That's simple ! Add this to your composer.json :

```
    "require": {
        "peekmo/jsonpath": "dev-master"
    }
```

You just have to require the vendor/autoload.php (if not already) and add the following use :

``` php
    use Peekmo\JsonPath\JsonStore;
```

## How it works ? ##

/!\ API Breaking Changes with new version !

Consider this json :

    { 
        "store": {
            "book": [ 
                { 
                    "category": "reference",
                    "author": "Nigel Rees",
                    "title": "Sayings of the Century",
                    "price": 8.95
                },
                { 
                    "category": "fiction",
                    "author": "Evelyn Waugh",
                    "title": "Sword of Honour",
                    "price": 12.99
                },
                {  
                    "category": "fiction",
                    "author": "Herman Melville",
                    "title": "Moby Dick",
                    "isbn": "0-553-21311-3",
                    "price": 8.99
                },
                {   
                    "category": "fiction",
                    "author": "J. R. R. Tolkien",
                    "title": "The Lord of the Rings",
                    "isbn": "0-395-19395-8",
                    "price": 22.99
                }
            ],
            "bicycle": {
                "color": "red",
                "price": 19.95
            }
        }
    }

- Transform your json into **array** (it works with object, but use an array for better performances)
- You can get values like this :

``` php
    <?php
    
    require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

    use Peekmo\JsonPath\JsonStore;

    $json = '...';

    $store = new JsonStore($json);

    // Returns an array with all categories from books which have an isbn attribute
    $res = $store->get("$..book[?(@.isbn)].category");
    
    $res = $store->get("$..book[?(@.isbn)].category", true); // You can set true to get only unique results

    ?>
```

It returns an array, you can so use default [functions](http://php.net/manual/fr/ref.array.php) on the result (Have unique key for example)
From 1.1.0, it returns an empty array if the node does not exists

- You can change a value like this :

``` php
    <?php
    
    require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

    use Peekmo\JsonPath\JsonStore;

    $json = '...';

    $store = new JsonStore($json);

    // Change the value of the first book's category
    $store->set("$..book[0].category", "superCategory");

    echo $store->toString();

    ?>
```

The value is passed by reference, so, when you are using a set, your object "$o" is modified.
It returns a boolean to know if the node has been modified or not

- You can add a value like this :

``` php
    <?php
    
    require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

    use Peekmo\JsonPath\JsonStore;

    $json = '...';

    $store = new JsonStore($json);

    // Add a new value in first book's array "key":"value"
    $store->add("$..book[0]", "value", "key");

    echo $store->toString();

    ?>
```

The parameter "key" is optional, a number will be set if you're not providing one.
It returns a boolean to know if the node has been modified or not

- You can remove an attribute like this :

``` php
    <?php
    
    require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

    use Peekmo\JsonPath\JsonStore;

    $json = '...';

    $store = new JsonStore($json);

    // Removes the attribute "category" from all books
    $store->remove("$..book.*.category");

    echo $store->toString();

    ?>
```

## Thanks ##

- Special thanks to **Stefan Goessner** for his previous work
