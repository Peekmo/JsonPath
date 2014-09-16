<?php

header('Content-Type: application/json');

require("vendor/autoload.php");
use Peekmo\JsonPath\JsonStore;

$json = '{
    "store": {
        "book": [ 
            { 
                "category": "reference",
                "author": "Nigel Rees",
                "title": "Sayings of the Century",
                "price": 8.95
            },
            { 
                "category": "01.02",
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
}';

$store = new JsonStore($json);

// Removes the attribute "category" from all books
$store->remove("$..book.*.category");

echo $store->toString();

?>