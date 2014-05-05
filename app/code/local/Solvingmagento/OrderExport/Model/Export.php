<?php
/**
 * Solvingmagento_OrderExport Export class
 * 
 * PHP version 5.3
 * 
 * @category  Knm
 * @package   Solvingmagento_OrderExport // FA RedDingo Sales API
 * @author    Brian Jordan <b.jordan@foundanimals.org>
 * @copyright 2014 Brian Jordan @ Found Animals
 * @link      http://foundanimals.org/
 */

class Solvingmagento_OrderExport_Model_Export
{
	/**
	 * Generates an array of data from sales order,
	 * sends data to RedDingo API, 
	 * creates a txt file containing the order details
	 */
	public function exportOrder($order){
		$dirPath = Mage::getBaseDir('var') . DS . 'export';
		
		//if the export directory does not exist, create it
		if (!is_dir($dirPath)){
			mkdir($dirPath, 0777, true);
		}


		function getOrderLineDetails($order){
			$lines = array();

			foreach ($order->getItemsCollection() as $prod){
				// set arrays
				$line = array();
				$categories = array();
				$options = array();
				$billing_address = array();
				$shipping_address = array();
					
				// collect data
				$_product = Mage::getModel('catalog/product')->load($prod->getProductId());

				$billing_address = $order->getBillingAddress()->getData();
				$shipping_address = $order->getShippingAddress()->getData();

				$cart = Mage::helper('checkout/cart')->getCart()->getQuote()->getAllItems();
				$catCollection = $_product->getCategoryCollection()->addAttributeToSelect('name');

				// collect custom options from item in cart
				foreach ($cart as $item){
					$_customOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());

					foreach($_customOptions['options'] as $_option){
						$array_key = $_option['label'];
						$option[$array_key] = $_option['value'];
					}

					$options = $option;
				}

				// collect categories
				foreach ($catCollection as $cat){
					$category = $cat->getName();
					$categories[] = $category;
				}

				// check if product has RedDingo ID category set
				if (in_array('ID', $categories)){
					$line['order_number'] = $order->getIncrementId();
					$line['billing'] = $billing_address;
					$line['shipping'] = $shipping_address;
					$line['product_name'] = $_product->getName();
					$line['sku'] = $_product->getSku();
					$line['order_quantity'] = (int)$prod->getQtyOrdered();
					$line['categories'] = $categories;
					$line['options'] = $options;
					$line['order_data'] = $orderData;

					$lines[] = $line;
				}
			}

			return $lines;
		} // getOrderLineDetails($order)


		// process Red Dingo products
		// uses curl_multi to submit orders
		$lines = getOrderLineDetails($order);
		$_results = null;

		if (b == b){
			$mh = curl_multi_init();
			$curl_array = array();
			$submitData = array();

			foreach ($lines as $i => $line){
				$url = "https://tags.reddingo.com/testing/api/stores/submit";

				$noEngraving = "true";
				$engravingLines = array();
				if (isset($line['options']['Line 1'])){
					$engravingLines[] = $line['options']['Line 1'];
					$noEngraving = "false";
				}
				if (isset($line['options']['Line 2'])){
					$engravingLines[] = $line['options']['Line 2'];
				}
				if (isset($line['options']['Line 3'])){
					$engravingLines[] = $line['options']['Line 3'];
				}

				$isDoubleSided = "false";
				$engravingLines2 = array();
				if (isset($line['options']['Back Line 1'])){
					$engravingLines2[] = $line['options']['Back Line 1'];
					$isDoubleSided = "true";
				}
				if (isset($line['options']['Back Line 2'])){
					$engravingLines2[] = $line['options']['Back Line 2'];
				}
				if (isset($line['options']['Back Line 3'])){
					$engravingLines2[] = $line['options']['Back Line 3'];
				}

				$orderData = array(
					"ownerFirstName" => $line['shipping']['firstname'],
					"ownerLastName" => $line['shipping']['lastname'],
					"petName" => "PET NAME",
					"ownerPhone" => $line['shipping']['telephone'],
					"ownerEmail" => $line['shipping']['email'],
					"ownerAddress" => $line['shipping']['street'],
					"ownerSuburbTownCity" => $line['shipping']['city'],
					"ownerStateProvinceRegion" => $line['shipping']['region'],
					"ownerPostZipCode" => $line['shipping']['postcode'],
					"ownerCountryCode" => $line['shipping']['country_id'],
					"receiver" => ''. $line['shipping']['firstname'] .' '. $line['shipping']['lasttname'] .'',
					"receiverAddress" => $line['shipping']['street'],
					"receiverSuburbTownCity" => $line['shipping']['city'],
					"receiverStateProvinceRegion" => $line['shipping']['region'],
					"receiverPostZipCode" => $line['shipping']['postcode'],
					"receiverCountryCode" => $line['shipping']['country_id'],
					"tag" => "02-BN-ZZ-ME", //"01-US-DB-ME", // needs proper sku
					"doubleSided" => $isDoubleSided, // this would be determined by the custom attributes
					"noEngraving" => $noEngraving, //"false", // if all fields are blank, this can be switched on
					"engravingLines" => $engravingLines, // "engravingLines":["LINE1","LINE2"]
					"engravingLines2" => $engravingLines2, // "engravingLines2":[] for double sided tags only, will refuse if doubleSided is false
					"sendToStore" => "false", // with false set, product will be shipped to either billing, or shipping address?
					"reference" => $order->getIncrementId(), //"REFERENCE", // system setting
					"emailStatusUpdates" => "false", // system setting
					"range" => "10217801" // system setting
				);

				$content = json_encode($orderData);
				$submitData[$i] = $orderData;

				$curl_array[$i] = curl_init();
				curl_setopt($curl_array[$i], CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
				curl_setopt($curl_array[$i], CURLOPT_USERPWD, "1:2"); // red dingo api key
				curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, array("Content-type: application/json"));
				curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_array[$i], CURLOPT_HEADER, true);
				curl_setopt($curl_array[$i], CURLOPT_POST, true);
				curl_setopt($curl_array[$i], CURLOPT_POSTFIELDS, $content);
				curl_setopt($curl_array[$i], CURLOPT_URL, $url);
				curl_multi_add_handle($mh, $curl_array[$i]);
		  	}

			$running = NULL;
			do {
			usleep(10000);
				curl_multi_exec($mh, $running);
			} while ($running > 0);


			// process data, build array for output to file
			// output files if success or error
			$res = array();
			foreach ($lines as $i => $line){
				$status = curl_getinfo($curl_array[$i]);
				$curl_response = curl_multi_getcontent($curl_array[$i]);

				preg_match_all("/{(.*?)}/", $curl_response, $results);
				$result = null;
				foreach ($results[0] as $result){
					$result = preg_replace("/[^0-9,.]/", "", $result);
					$result = preg_replace('/\D/', '', $result);
				}

				$res[$i]['rd_id'] = $result;
				$res[$i]['order_details'] = $line;
				$res[$i]['submit_data'] = $submitData[$i];
				$res[$i]['status'] = $curl_response;

				if ($status['http_code'] == 200){
					$_id = $line['rd_id'];
					file_put_contents(
						$dirPath. DS .'_red_dingo__'. $order->getIncrementId() .'_'. $res[$i]['rd_id'] .'.txt', 
						print_r($res[$i], true)
					);
				}
				else {
					$error = array();
					$error[] = $status;
					$error[] = $res[$i];

					file_put_contents(
						$dirPath. DS .'_red_dingo__'. $order->getIncrementId() .'_'. $res[$i]['rd_id'] .'_error_.txt', 
						print_r($error, true)
					);

					Mage::getSingleton('core/session')->addError("An error was encountered while processing your Red Dingo order, please save this page and contact info@adoptandshop for help. Error code: <pre>". print_r($error, true) ."</pre>");
					$this->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
					$this->getResponse()->sendResponse();
					exit;
				}
			}


			// // confirm API responses are successful
			// while ($done = curl_multi_info_read($mh)){
			// 	$info = curl_getinfo($done['handle']);

			// 	if ($info['http_code'] == 200){
			// 		// if the server returns a 200 status, do nothing
			// 		// $res['servers_responses'][] = 'is status 200';
			// 	}
			// 	else {
			// 		// if there is an error in a order, throw an error and return to cart
			//		Mage::getSingleton('core/session')->addError("An error was encountered while processing your Red Dingo order, please contact info@adoptandshop for help. Error code: " . print_r($info['http_code'], true) );
			//		$this->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
			//		$this->getResponse()->sendResponse();
			//		exit;
			// 	}
			// }


			// output orders to file
			// foreach ($res as $i => $r){
			// 	$_id = $r['rd_id'];
			// 	file_put_contents(
			// 		$dirPath. DS .'_red_dingo__'. $order->getIncrementId() .'_'. $_id .'.txt', 
			// 		print_r($r, true)
			// 	);
			// }

			


			// clean up curl requests
			foreach ($lines as $i => $line){
				curl_multi_remove_handle($mh, $curl_array[$i]);
			}

			curl_multi_close($mh);
		}


		// this is used for testing and debugging
		// uncomment to test output at cat checkout
		// Mage::getSingleton('core/session')->addError('<pre>'. print_r($res, true) .'</pre>');
		// $this->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
		// $this->getResponse()->sendResponse();
		// exit;


		return true;
	}
}