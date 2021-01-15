<?php

// Autoload files using the Composer autoloader.
require_once '../vendor/autoload.php';
require_once 'ItemModel.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

try {
	$params = [
		'OPERATION-NAME' => 'findItemsAdvanced',
		'SERVICE-VERSION' => '1.0.0',
		'SECURITY-APPNAME' => 'WandoInt-217b-42d8-a699-e79808dd505e',
		'RESPONSE-DATA-FORMAT' => 'json',
		'keywords' => $_GET['keywords']
	];
	$query = '';
	if ($_GET['price_min']) {
		$query .= 'itemFilter(0).name=MinPrice&itemFilter(0).value=' . $_GET['price_min'] . '&itemFilter(0).paramName=Currency&itemFilter(0).paramValue=USD';
	}

	if ($_GET['price_max']) {
		$query .= '&itemFilter(1).name=MaxPrice&itemFilter(1).value=' . $_GET['price_max'] . '&itemFilter(1).paramName=Currency&itemFilter(1).paramValue=USD';
	}

	if ($_GET['sorting']) {
		if ($_GET['sorting'] != 'default') {
			$_GET['sorting'] = 'PricePlusShippingLowest';
		}
		$query .= '&sortOrder=' . $_GET['sorting'];
	}


	$client = new Client();
	$res = $client->request('GET', 'https://svcs.sandbox.ebay.com/services/search/FindingService/v1?' . http_build_query($params) . '&REST-PAYLOAD&outputSelector=AspectHistogram&' . $query);
	$status =  $res->getStatusCode();
	$headers = $res->getHeader('content-type');
	$body = \GuzzleHttp\json_decode($res->getBody(), true);
	if ($body['findItemsAdvancedResponse'][0]['searchResult'][0]['@count'] <= 0) {
		exit('No item Found');
	}
	$apiItems = $body['findItemsAdvancedResponse'][0]['searchResult'][0]['item'];

	// echo '<pre>';
	// print_r($apiItems);
	// die();

	$formattedItems = [];
	foreach ($apiItems as $key => $item) {

		$categoryId = $item['primaryCategory'][0]['categoryId'] ?? 0;

		$histogramsParams = [
			'OPERATION-NAME' => 'getHistograms',
			'SERVICE-VERSION' => '1.0.0',
			'SECURITY-APPNAME' => 'WandoInt-217b-42d8-a699-e79808dd505e',
			'RESPONSE-DATA-FORMAT' => 'json',
			'REST-PAYLOAD' => '',
			'categoryId' => $categoryId
		];

		$client = new Client();
		$result = $client->request('GET', 'https://svcs.sandbox.ebay.com/services/search/FindingService/v1?' . http_build_query($histogramsParams));
		$status =  $result->getStatusCode();
		$headers = $res->getHeader('content-type');
		$histgrams = \GuzzleHttp\json_decode($result->getBody(), true);
		$brand = '';

		if(isset($histgrams['getHistogramsResponse'][0]['aspectHistogramContainer']))
		{
			$brand = $histgrams['getHistogramsResponse'][0]['aspectHistogramContainer'][0]['brand'][0]['brandDisplayName'][0] ?? '';
		}

		$prices = getPricings($item['sellingStatus'][0] ?? []);
		$shippingPrices = getShippingPricings($item['shippingInfo'][0] ?? []);

		$model = new ItemModel();

		$model->provider = 'ebay';
		$model->item_id = $item['itemId'][0] ?? 0;
		$model->click_out_link = $item['viewItemURL'][0] ?? '';
		$model->main_photo_url = $item['galleryURL'][0] ?? '';
		$model->price = $prices['price'] ?? 0;
		$model->price_currency = $prices['currency'] ?? '';
		$model->shipping_price = $shippingPrices['price'] ?? 0;
		$model->title = $item['title'][0];
		$model->valid_until = $prices['valid_until'] ?? '';
		$model->brand = $brand;

		array_push($formattedItems, $model);
	}
	$html = '<table>
				<th>
					<td>Title</td>
					<td>Provider</td>
					<td>Item ID</td>
					<td>Link</td>
					<td>Image</td>
					<td>Price</td>
					<td>Currency</td>
					<td>Shipping Price</td>
					<td>Valid Until</td>
					<td>Brand</td>
				</th>';

	foreach($formattedItems as $eBayItem)
	{
		$html .= '<tr>';
		$html .= '<td>'. $eBayItem->title .'</td>';
		$html .= '<td>'. $eBayItem->provider .'</td>';
		$html .= '<td>'. $eBayItem->item_id .'</td>';
		$html .= '<td>'. $eBayItem->click_out_link .'</td>';
		$html .= '<td>'. $eBayItem->main_photo_url .'</td>';
		$html .= '<td>'. $eBayItem->price .'</td>';
		$html .= '<td>'. $eBayItem->price_currency .'</td>';
		$html .= '<td>'. $eBayItem->shipping_price .'</td>';
		$html .= '<td>'. $eBayItem->valid_until .'</td>';
		$html .= '<td>'. $eBayItem->brand .'</td>';
		$html .= '</tr>';

	}

	
	$html .= '</table>';

	echo $html;

} catch (RequestException $e) {
	print_r($e->getMessage());
	if ($e->hasResponse()) {
		print_r($e->getMessage());
	}
}

function getPricings($prices)
{

	if ($prices['currentPrice']) {
		$data['price'] = $prices['currentPrice'][0]['__value__'] ?? 0;
		$data['currency'] = $prices['currentPrice'][0]['@currencyId'] ?? '';
		$data['valid_until'] = $prices['timeLeft'][0]['@currencyId'] ?? '';
	}
	if ($prices['timeLeft']) {
		$data['valid_until'] = $prices['timeLeft'][0] ?? '';
	}

	return $data;
}

function getShippingPricings($prices)
{
	$data = [];

	if (count($prices) > 0 && isset($prices['shippingServiceCost'])) {
		$data['price'] = $prices['shippingServiceCost'][0]['__value__'] ?? 0;
		$data['currency'] = $prices['shippingServiceCost'][0]['@currencyId'] ?? '';
	}

	return $data;
}
