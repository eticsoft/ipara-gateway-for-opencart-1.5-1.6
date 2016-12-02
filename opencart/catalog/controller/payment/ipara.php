<?php

class ControllerPaymentIpara extends Controller
{

	public function index()
	{
		//$this->load->language('payment/ipara');
		$this->document->addStyle('catalog/view/theme/default/stylesheet/ipara_form.css');

		// $this->data['text_instruction'] = $this->language->get('text_instruction');
		// $this->data['text_description'] = $this->language->get('text_description');
		// $this->data['text_payment'] = $this->language->get('text_payment');
		// $this->data['text_loading'] = $this->language->get('text_loading');

		//$this->data['button_confirm'] = $this->language->get('button_confirm');

		$this->data['bank'] = nl2br($this->config->get('ipara_bank' . $this->config->get('config_language_id')));

		$this->data['continue'] = $this->url->link('checkout/success');
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/ipara.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/ipara.tpl';
		} else {
			$this->template = 'default/template/payment/ipara.tpl';
		}
		$this->render();

	}

	public function paymentform()
	{

		$this->load->model('checkout/order');
		$this->load->model('setting/setting');
		require_once(DIR_APPLICATION . 'controller/payment/iparaconfig.php');
		require_once(DIR_APPLICATION . 'controller/payment/ipara_payment.php');
		if(!isset($this->session->data['order_id']) OR !$this->session->data['order_id'])
			die('Sipariş ID bulunamadı');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		
		
		$error_message = false;
		$cc_form_key = md5($order_info['order_id'] . $order_info['store_url']);
		$total_cart = $order_info['total'];

		if (isset($this->request->post['result']) AND isset($this->request->post['hash']) AND isset($this->request->get['tdvalidate'])) { // hash varsa
			$record = $this->iPara3DValidate();
			$this->saveRecord($record);
			$error_message = $record['result_message'];
			$order_id = $this->session->data['order_id'];
		}
		if (isset($this->request->post['cc_form_key']) && $this->request->post['cc_form_key'] == $cc_form_key) {
			$record = $this->post2Ipara();
			$this->saveRecord($record);
			$error_message = $record['result_message'];
			$order_id = $this->session->data['order_id'];
		}
		if (isset($record['result']) AND $record['result']) {
			$order_id = $this->session->data['order_id'];
			$record['id_order'] = $order_id;
			$this->saveRecord($record);
			$comment = $this->record2Table($record);
			$this->session->data['payment_method']['code'] = 'ipara';
			$this->model_checkout_order->confirm($order_id, $this->config->get('ipara_order_status_id'));
            $this->model_checkout_order->update($order_id, $this->config->get('ipara_order_status_id'), $comment, false);
            $this->response->redirect($this->url->link('checkout/success'));
		}

		$this->children = array(
			'common/column_left',
			'common/column_right',
			'common/content_top',
			'common/content_bottom',
			'common/footer',
			'common/header'
		);

		$ipara_rates = iParaConfig::calculatePrices($total_cart, $this->config->get('ipara_rates'));
		$this->data['cc_form_key'] = $cc_form_key;
		$this->data['rates'] = $ipara_rates;
		$this->data['error_message'] = $error_message;
		$this->data['cart_id'] = $this->session->data['order_id'];
		$this->data['form_link'] = $this->url->link('payment/ipara/paymentform');


		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/ipara_ccform.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/ipara_ccform.tpl';
		} else {
			$this->template = 'default/template/payment/ipara_ccform.tpl';
		}
		$this->response->setOutput($this->render());
	}

	/*
	 * Post CC data to iPara gateWay
	 */

	function post2Ipara()
	{
		$this->load->model('checkout/order');
		require_once(DIR_APPLICATION . 'controller/payment/iparaconfig.php');
		require_once(DIR_APPLICATION . 'controller/payment/ipara_payment.php');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$prices = iParaConfig::calculatePrices($order_info['total'], $this->config->get('ipara_rates'));

		$record = array(
			'result_code' => '0',
			'result_message' => '',
			'result' => false
		);

		$ins = (int) $this->request->post['cc_installment'];
		// $customer = new Customer($cart->id_customer);
		// $order_sum = $cart->getSummaryDetails();

		$amount = (float) $prices[key($prices)]['installments'][$ins]['total'];
		$installment = $ins;
		$orderid = 'ETIC' . $this->session->data['order_id'] . time();
		$public_key = $this->config->get('ipara_publickey');
		$private_key = $this->config->get('ipara_privatekey');
		$ipara_3d_mode = $this->config->get('ipara_3d_mode');
		$ipara_products = array();  // aşağıda düzenlenecek;
		$ipara_address = array();  //aşağıda düzenlenecek
		$ipara_purchaser = array();  // aşağıda düzenlenecek


		$expire_date = explode('/', $this->request->post['cc_expiry']);

		$ipara_card = array(// Kredi kartı bilgileri
			'owner_name' => $this->request->post['cc_name'],
			'number' => str_replace(' ', '', $this->request->post['cc_number']),
			'expire_month' => str_replace(' ', '', $expire_date[0]),
			'expire_year' => str_replace(' ', '', $expire_date[1]),
			'cvc' => $this->request->post['cc_cvc']
		);


		$record = array(
			'id_cart' => $this->session->data['order_id'],
			'id_customer' => $this->session->data['customer_id'],
			'amount' => $order_info['total'],
			'amount_paid' => "",
			'installment' => $ins,
			'cc_name' => $ipara_card['owner_name'],
			'cc_expiry' => str_replace(' ', '', $expire_date[0]) . str_replace(' ', '', $expire_date[1]),
			'cc_number' => substr($ipara_card['number'], 0, 6) . 'XXXXXXXX' . substr($ipara_card['number'], -2),
			'id_ipara' => $orderid,
			'result_code' => '0',
			'result_message' => '',
			'result' => false
		);

		// Müşteri
		$ipara_purchaser['name'] = $order_info['firstname'];
		$ipara_purchaser['surname'] = $order_info['lastname'];
		$ipara_purchaser['email'] = $order_info['email'];
		$ipara_purchaser['birthdate'] = NULL;
		$ipara_purchaser['gsm_number'] = NULL;
		$ipara_purchaser['tc_certificate_number'] = NULL;


		// ADRES
		$ipara_address['name'] = $order_info['firstname'];
		$ipara_address['surname'] = $order_info['lastname'];
		$ipara_address['address'] = $order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2'];
		$ipara_address['zipcode'] = $order_info['shipping_postcode'];
		$ipara_address['city_code'] = 34;
		$ipara_address['city_text'] = $order_info['shipping_city'];
		$ipara_address['country_code'] = "TR";
		$ipara_address['country_text'] = "Türkiye";
		$ipara_address['phone_number'] = $order_info['telephone'];
		$ipara_address['tax_number'] = NULL;
		$ipara_address['tax_office'] = NULL;
		$ipara_address['tc_certificate_number'] = NULL;
		$ipara_address['company_name'] = NULL;


		// ÜRÜNLER
		$extra_id = 0;
		foreach ($this->cart->getProducts() as $item) {
			if ($item['total'] == 0)
				continue;

			$ipara_products[$extra_id]['title'] = $item['name'];
			$ipara_products[$extra_id]['code'] = $item['product_id'] . $extra_id;
			$ipara_products[$extra_id]['quantity'] = $item['quantity'];
			$ipara_products[$extra_id]['price'] = $item['price'];
			$extra_id++;
		}
		// Gerekli değil debug için özel olarak yarattık
		$debug_array = array(
			'ipara_products' => $ipara_products,
			'ipara_purchaser' => $ipara_purchaser,
			'ipara_address' => $ipara_address,
			'installment' => $installment,
			'orderid' => $orderid,
			'amount' => $amount,
			'public_key' => $public_key,
			'private_key' => $private_key
		);
		$record['debug'] = serialize($debug_array);

		$obj = new iParaPayment();
		$obj->public_key = $public_key;
		$obj->private_key = $private_key;
		$obj->mode = "P";
		$obj->order_id = $orderid;
		$obj->installment = $installment;
		$obj->amount = $amount;
		$obj->vendor_id = 4;
		$obj->echo = "eticsoft.com";
		$obj->products = $ipara_products;
		$obj->shipping_address = $ipara_address;
		$obj->invoice_address = $ipara_address;
		$obj->card = $ipara_card;
		$obj->purchaser = $ipara_purchaser;
		$obj->success_url = $this->url->link('payment/ipara/paymentform', 'tdvalidate=1');
		$obj->failure_url = $this->url->link('payment/ipara/paymentform', 'tdvalidate=1');

		$check_ipara = $this->getiParaOptions($ipara_card['number']);

		if (!$check_ipara OR $check_ipara == NULL) {
			$check_ipara = (object) array(
						'result_code' => "Webservis çalışmıyor",
						'supportsInstallment' => "1",
						'cardThreeDSecureMandatory' => "1",
						'merchantThreeDSecureMandatory' => "1",
						'result' => "1",
			);
		}


		if ($check_ipara->result == '0') {
			$record['result_code'] = 'REST-' . $check_ipara->errorCode;
			$record['result_message'] = 'WebServis Hatası ' . $check_ipara->errorMessage;
			$record['result'] = false;
			return $record;
		}
		if ($check_ipara->supportsInstallment != '1' AND (string) $installment != "1") {
			$record['result_code'] = 'REST-3D-1';
			$record['result_message'] = 'Kartınız taksitli alışverişi desteklemiyor. Lütfen tek çekim olarak deneyiniz';
			$record['result'] = false;
			return $record;
		}

		$td_mode = true;

		if ($check_ipara->cardThreeDSecureMandatory == '0'
				AND $check_ipara->merchantThreeDSecureMandatory == '0')
			$td_mode = false;

		if ($ipara_3d_mode == 'on')
			$td_mode = true;
		if ($ipara_3d_mode == 'off')
			$td_mode = false;



		if ($td_mode) {
			try {
				$record['result_code'] = '3D-R';
				$record['result_message'] = '3D yönlendimesi yapıldı. Dönüş bekleniyor';
				$record['result'] = false;
				$this->saveRecord($record);
				$response = $obj->payThreeD();
				exit;
			} catch (Exception $e) {
				$record['result_code'] = 'IPARA-LIB-ERROR';
				$record['result_message'] = $e->getMessage();
				$record['result'] = false;
				return $record;
			}
		}
		/* PAY Via API */

		$response = $obj->pay();
		$record['result_code'] = $response['error_code'];
		$record['id_ipara'] = $response['order_id'];
		$record['result_message'] = $response['error_message'];
		$record['result'] = (string) $response['result'] == "1" ? true : false;
		$record['amount_paid'] = $amount;
		return $record;
	}

	function iPara3DValidate()
	{
		$this->load->model('checkout/order');
		$this->load->model('setting/setting');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$public_key = $this->config->get('ipara_publickey');
		$private_key = $this->config->get('ipara_privatekey');

		//begin HASH verification
		$response = $this->request->post;
		
		$record = $this->getRecordByiParaId($response['orderId']);

		if (!$record)
			die('Order id not found');

		$record['result_code'] = $this->request->post['errorCode'];
		$record['result_message'] = $this->request->post['errorMessage'];
		$record['id_ipara'] = $this->request->post['orderId'];
		$record['result'] = false;

		$hash_text = $response['orderId']
				. $response['result']
				. $response['amount']
				. $response['mode']
				. $response['errorCode']
				. $response['errorMessage']
				. $response['transactionDate']
				. $response['publicKey']
				. $private_key;
		$hash = base64_encode(sha1($hash_text, true));

		if ($hash != $response['hash']) { // has yanlışsa
			$record['result_message'] = "Hash uyumlu değil";
			return $record;
		} else { // hash doğru
			if ($response['result'] == 1) { // 3D doğrulama başarılı çekim yap
				$ipara_products = array();  // aşağıda düzenlenecek;
				$ipara_address = array();  //aşağıda düzenlenecek
				$ipara_purchaser = array();  // aşağıda düzenlenecek
				// Müşteri
				$ipara_purchaser['name'] = $order_info['firstname'];
				$ipara_purchaser['surname'] = $order_info['lastname'];
				$ipara_purchaser['email'] = $order_info['email'];
				$ipara_purchaser['birthdate'] = NULL;
				$ipara_purchaser['gsm_number'] = NULL;
				$ipara_purchaser['tc_certificate_number'] = NULL;

				// ADRES
				$ipara_address['name'] = $order_info['firstname'];
				$ipara_address['surname'] = $order_info['lastname'];
				$ipara_address['address'] = $order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2'];
				$ipara_address['zipcode'] = $order_info['shipping_postcode'];
				$ipara_address['city_code'] = 34;
				$ipara_address['city_text'] = $order_info['shipping_city'];
				$ipara_address['country_code'] = "TR";
				$ipara_address['country_text'] = "Türkiye";
				$ipara_address['phone_number'] = $order_info['telephone'];
				$ipara_address['tax_number'] = NULL;
				$ipara_address['tax_office'] = NULL;
				$ipara_address['tc_certificate_number'] = NULL;
				$ipara_address['company_name'] =$order_info['payment_company'];

				// ÜRÜNLER
				$extra_id = 0;
				foreach ($this->cart->getProducts() as $item) {
					if ($item['total'] == 0)
						continue;
					$ipara_products[$extra_id]['title'] = $item['name'];
					$ipara_products[$extra_id]['code'] = $item['product_id'] . $extra_id;
					$ipara_products[$extra_id]['quantity'] = $item['quantity'];
					$ipara_products[$extra_id]['price'] = $item['price'];
					$extra_id++;
				}

				$obj = new iParaPayment();
				$obj->public_key = $public_key;
				$obj->private_key = $private_key;
				$obj->mode = "P";
				$obj->three_d_secure_code = $response['threeDSecureCode'];
				$obj->order_id = $response['orderId'];
				$obj->amount = $response['amount'] / 100;
				$obj->echo = "EticSoft";
				$obj->vendor_id = 4;
				$obj->products = $ipara_products;
				$obj->shipping_address = $ipara_address;
				$obj->invoice_address = $ipara_address;
				$obj->purchaser = $ipara_purchaser;

				try {
					$response = $obj->pay();
					$record['result_message'] = (string)$response['error_message'];
					$record['result_code'] = (string)$response['error_code'];
					$record['amount_paid'] = (float)$response['amount'] / 100;
					$record['result'] = (int)$response['result'];
					return $record;
				} catch (Exception $e) {
					// çekim başarısız doğrulama başarılı
					$record['result_message'].= "Post error after 3DS";
					$record['result_code'] = "8888";
					return $record;
				}
			} else {
				// 3D basarisiz
				$record['result_message'] .= ' -3DS doğrulama yapılamadı ';
				return $record;
			}
		}
	}

	private function addRecord($record)
	{
		return $this->db->query($this->insertRowQuery('ipara_payment', $record));
	}

	private function updateRecordByOrderId($record)
	{
		return $this->db->query($this->updateRowQuery('ipara_payment', $record, array('id_record' => (int) $record['id_record'])));
	}

	private function updateRecordByIparaId($record)
	{
		return $this->db->query($this->updateRowQuery('ipara_payment', $record, array('id_ipara' => $record['id_ipara'])));
	}

	private function updateRecordByCartId($record)
	{
		return $this->db->query($this->updateRowQuery('ipara_payment', $record, array('id_cart' => (int) $record['id_cart'])));
	}

	public function saveRecord($record)
	{
		$record['date_create'] = date("Y-m-d h:i:s");
		if (isset($record['id_record'])
				AND $record['id_record']
				AND $this->getRecordByOrderId($record['id_record']))
			return $this->updateRecordByOrderId($record);

		if (isset($record['id_ipara'])
				AND $record['id_ipara']
				AND $this->getRecordByiParaId($record['id_ipara']))
			return $this->updateRecordByIparaId($record);

		if (isset($record['id_cart'])
				AND $record['id_cart']
				AND $this->getRecordByCartId($record['id_cart']))
			return $this->updateRecordByCartId($record);

		return $this->addRecord($record);
	}

	public function getRecordByOrderId($id_order)
	{
		$row = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'ipara_payment` '
				. 'WHERE `id_order` = ' . (int) $id_order);
		return $row->num_rows == 0 ? false : $row->row;
	}

	public function getRecordByiParaId($id_ipara)
	{
		$row = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'ipara_payment` '
				. 'WHERE `id_ipara` = "' . $id_ipara . '"');
		return $row->num_rows == 0 ? false : $row->row;
	}

	public function getRecordByCartId($id_cart)
	{
		$row = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'ipara_payment` '
				. 'WHERE `id_cart` = ' . (int) $id_cart);
		return $row->num_rows == 0 ? false : $row->row;
	}

	private function getiParaOptions($cc)
	{

		$this->load->model('setting/setting');
		$publicKey = $this->config->get('ipara_publickey');
		$privateKey = $this->config->get('ipara_privatekey');

		$binNumber = substr($cc, 0, 6);
		$transactionDate = date("Y-m-d H:i:s");
		$token = $publicKey . ":" . base64_encode(sha1($privateKey . $binNumber . $transactionDate, true));
		$data = array("binNumber" => $binNumber);
		$data_string = json_encode($data);

		$ch = curl_init('https://api.ipara.com/rest/payment/bin/lookup');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length:' . strlen($data_string),
			'token:' . $token,
			'transactionDate:' . $transactionDate,
			'version:' . '1.0',
		));

		$response = curl_exec($ch);
		return json_decode($response);
	}

	private function updateRowQuery($table, $array, $where, $what = null, $deb = false)
	{
		$q = "UPDATE `" . DB_PREFIX . "$table` SET ";
		$i = count($array);
		foreach ($array as $k => $v) {
			$q .= '`' . $k . '` = ' . "'" . $this->escape($v) . "'";
			$i--;
			if ($i > 0)
				$q .=" ,\n";
		}
		$q .= ' WHERE ';
		if (is_array($where)) {
			$i = count($where);
			foreach ($where as $k => $v) {
				$i--;
				$q .= '`' . $k . '` = \'' . $this->escape($v) . '\' ';
				if ($i != 0)
					$q .= ' AND ';
			}
		} else
			$q .= "`$where` = '" . $this->escape($what) . "' LIMIT 1";
		if ($deb)
			echo $q;
		return $q;
	}

	private function insertRowQuery($table, $array, $deb = false)
	{
		$f = '';
		$d = '';
		$q = "INSERT INTO `" . DB_PREFIX . "$table` ( ";
		$i = count($array);
		foreach ($array as $k => $v) {
			if (is_array($v))
				print_r($v);
			$f .= "`" . $k . "`";
			$d .= "'" . $this->escape($v) . "'";
			$i--;
			if ($i > 0) {
				$f .=", ";
				$d .=", ";
			}
		}
		$q .= $f . ') VALUES (' . $this->escape($d) . ' )';
		if ($deb)
			echo $q;
		return $q;
	}

	private function escape($var)
	{
		return $var;
	}

	private function record2Table($array)
	{
		if (!is_array($array))
			return;
		$r = 'iPara işlem No:' . $array['id_ipara'] . " ";
		$r .= 'Sepet toplamı:' . $array['amount'] . " ";
		$r .= 'Ödenen:' . $array['amount_paid'] . " ";
		$r .= 'Komisyon:' . $array['amount_paid'] - $array['amount'] . " ";
		$r .= 'Taksit:' . $array['installment'] . " ";
		$r .= 'Kart:' . $array['cc_number'] . ' - ' . $array['cc_name'] . " ";
		return $r . 'Cevap:' . $array['result_code'] . ':' . $array['result_message'] . " ";
	}

}
