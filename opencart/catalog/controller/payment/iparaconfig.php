<?php

Class iParaConfig
{

    const max_installment = 9;

    public $max_installment = 9;
    public $id_order = false;
    public $id_customer = false;
    public $id_invoice = false;
    public $id_cart = false;
    public $id_bank = false;
    public $cc = array(
	'name' => '',
	'no' => '',
	'cvv' => '',
	'expire_year' => '',
	'expire_month' => '',
    );
    public $amount = 0.00;
    public $cart_amount = 0.00;
    public $total_products = 0.00;
    public $total_shipping = 0.00;
    public $total_tax = 0.00;
    public $installment = 0;
    public $customer = array(
	'firstname' => '',
	'lastname' => '',
	'birthday' => '',
	'address' => '',
	'city' => '',
	'country' => '',
	'zip' => '',
	'phone' => '',
	'mobile' => '',
	'email' => '',
    );
    public $items = array(
	'products' => array(),
	'shipping' => false
    );
    public $bank = false;

    public static function getAvailablePrograms()
    {
	return array(
	    'axess' => array('name' => 'Axess', 'bank' => 'Akbank A.Ş.'),
	    'word' => array('name' => 'WordCard', 'bank' => 'Yapı Kredi Bankası'),
	    'bonus' => array('name' => 'BonusCard', 'bank' => 'Garanti Bankası A.Ş.'),
	    'cardfinans' => array('name' => 'CardFinans', 'bank' => 'FinansBank A.Ş.'),
	    'asyacard' => array('name' => 'AysaCard', 'bank' => 'BankAsya A.Ş.'),
	    'maximum' => array('name' => 'Maximum', 'bank' => 'T.C. İş Bankası'),
	    'paraf' => array('name' => 'Paraf', 'bank' => 'T Halk Bankası A.Ş.'),
	);
    }

    public static function setRatesFromPost($posted_data)
    {
	$banks = iParaConfig::getAvailablePrograms();
	$return = array();
	foreach ($banks as $k => $v) {
	    $return[$k] = array();
	    for ($i = 1; $i <= self::max_installment; $i++) {
		$return[$k][$i] = isset($posted_data[$k]['installments'][$i]) ? ((float) $posted_data[$k]['installments'][$i]) : 0.0;
		if ($posted_data[$k]['installments'][$i]['passive']) {
		    $return[$k][$i] = -1.0;
		}
	    }
	}
	return $return;
    }

    public static function setRatesDefault()
    {
	$banks = iParaConfig::getAvailablePrograms();
	$return = array();
	foreach ($banks as $k => $v) {
	    $return[$k] = array();
	    for ($i = 1; $i <= self::max_installment; $i++) {
		$return[$k]['installments'][$i] = (float) (1 + $i + ($i / 5) + 0.1);
		if ($i == 1)
		    $return[$k]['installments'][$i] = 0.00;
	    }
	}
	return $return;
    }

    public static function createRatesUpdateForm($rates)
    {
	$return = '<table class="ipara_table table">'
		. '<thead>'
		. '<tr><th>Banka</th>';
	for ($i = 1; $i <= self::max_installment; $i++) {
	    $return .= '<th>' . $i . ' taksit</th>';
	}
	$return .= '</tr></thead><tbody>';

	$banks = iParaConfig::getAvailablePrograms();
	foreach ($banks as $k => $v) {
	    $return .= '<tr>'
		    . '<th><img src="'.HTTPS_CATALOG.'catalog/view/theme/default/image/ipara/banks/' . $k . '.png"></th>';
	    for ($i = 1; $i <= self::max_installment; $i++) {
		$return .= '<td><input type="number" step="0.001" maxlength="4" size="4" '
			. ' value="' . ((float) $rates[$k]['installments'][$i]) . '"'
			. ' name="ipara_rates[' . $k . '][installments][' . $i . ']"/></td>';
	    }
	    $return .= '</tr>';
	}
	$return .= '</tbody></table>';
	return $return;
    }

    public static function calculatePrices($price, $rates)
    {
	$banks = iParaConfig::getAvailablePrograms();
	$return = array();
	foreach ($banks as $k => $v) {
	    $return[$k] = array();
	    for ($i = 1; $i <= self::max_installment; $i++) {
			$return[$k]['installments'][$i] = array(
				'total' => number_format((((100 + $rates[$k]['installments'][$i]) * $price) / 100), 2, '.', ''),
				'monthly' => number_format((((100 + $rates[$k]['installments'][$i]) * $price) / 100) / $i, 2, '.', ''),
			);
	    }
	}
	return $return;
    }

    public function getRotatedRates($price, $rates)
    {
	$prices = iParaConfig::calculatePrices($price, $rates);
	for ($i = 1; $i <= self::max_installment; $i++) {
	    
	}
    }

    public static function createInstallmentsForm($price, $rates)
    {
	$prices = iParaConfig::calculatePrices($price, $rates);
	$return = '<table class="ipara_table table">'
		. '<thead>'
		. '<tr><th>Banka</th>';
	for ($i = 1; $i <= self::max_installment; $i++) {
	    $return .= '<th>' . $i . ' taksit</th>';
	}
	$return .= '</tr></thead><tbody>';

	$banks = iParaConfig::getAvailablePrograms();
	foreach ($banks as $k => $v) {
	    $return .= '<tr>'
		    . '<th><img src="/modules/ipara/views/img/banks/' . $k . '.png"></th>';
	    for ($i = 1; $i <= self::max_installment; $i++) {
		$return .= '<td><input type="number" step="0.001" maxlength="4" size="4" '
			. ' value="' . ((float) $rates[$k]['installments'][$i]) . '"'
			. ' name="ipara_rates[' . $k . '][installments][' . $i . ']"/></td>';
	    }
	    $return .= '</tr>';
	}
	$return .= '</tbody></table>';
	return $return;
    }

}
