<?php

// Autoload files using the Composer autoloader.
require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ItemModel
{
	public $provider; //(=“ebay“)
	public $item_id;
	public $click_out_link;
	public $main_photo_url;
	public $price;
	public $price_currency;
	public $shipping_price;
	public $title;
	public $description;
	public $valid_until;
	public $brand;
	
}