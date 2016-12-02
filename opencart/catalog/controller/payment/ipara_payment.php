<?php header('Content-type: text/html; charset=utf-8');

class iParaPayment
{
    // Istek Adresleri
    private $auth_url = "https://api.ipara.com/rest/payment/auth";
    private $three_d_url = "https://www.ipara.com/3dgate";
    private $version = "1.0";

    // Istek degiskenleri
    private $mode;
    private $three_d;
    private $order_id;
    private $installment;
    private $amount;
    private $vendor_id = 4;
    private $echo;
    private $products;
    private $shipping_address;
    private $invoice_address;
    private $card;
    private $purchaser;
    private $private_key;
    private $public_key;
    private $success_url;
    private $failure_url;
    private $three_d_secure_code;

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
        return $this;
    }

    // API ile Odeme Metodu
    public function pay()
    {
        $this->three_d = "false";

        if (isset($this->three_d_secure_code)) {
            $this->three_d = "true";
        }

        $xml_data = $this->prepareXML();
        $output = $this->calliParaAuthService($xml_data);
        if ($output == NULL) {
            throw new Exception("Ödeme cevabı boş");
        }
        $response = $this->prepareResponse($output);
        $this->validateResponse($response);

        $response['amount'] = number_format((float)($response['amount'] / 100), 2, '.', '');

        return $response;
    }

    // 3D Secure ile Odeme Methodu
    public function payThreeD()
    {
        $timestamp = date("Y-m-d H:i:s");
        $hash_text = $this->private_key . $this->order_id . number_format((float)$this->amount, 2, '', '') . $this->mode . $this->card['owner_name'] .
            $this->card['number'] . $this->card['expire_month'] . $this->card['expire_year'] . $this->card['cvc'] .
            $this->purchaser['name'] . $this->purchaser['surname'] . $this->purchaser['email'] . $timestamp;
        $token = $this->public_key . ":" . base64_encode(sha1($hash_text, true));

        print("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">");
        print("<html>");
        print("<body>");
        print("<form action=\"" . $this->three_d_url . "\" method=\"post\" id=\"three_d_form\"/>");
        print("<input type=\"hidden\" name=\"orderId\" value=\"" . $this->order_id . "\"/>");
        print("<input type=\"hidden\" name=\"amount\" value=\"" . number_format((float)$this->amount, 2, '', '') . "\"/>");
        print("<input type=\"hidden\" name=\"cardOwnerName\" value=\"" . $this->card['owner_name'] . "\"/>");
        print("<input type=\"hidden\" name=\"cardNumber\" value=\"" . $this->card['number'] . "\"/>");
        print("<input type=\"hidden\" name=\"cardExpireMonth\" value=\"" . $this->card['expire_month'] . "\"/>");
        print("<input type=\"hidden\" name=\"cardExpireYear\" value=\"" . $this->card['expire_year'] . "\"/>");
        print("<input type=\"hidden\" name=\"installment\" value=\"" . $this->installment . "\"/>");
        print("<input type=\"hidden\" name=\"cardCvc\" value=\"" . $this->card['cvc'] . "\"/>");
        print("<input type=\"hidden\" name=\"mode\" value=\"" . $this->mode . "\"/>");
        print("<input type=\"hidden\" name=\"purchaserName\" value=\"" . $this->purchaser['name'] . "\"/>");
        print("<input type=\"hidden\" name=\"purchaserSurname\" value=\"" . $this->purchaser['surname'] . "\"/>");
        print("<input type=\"hidden\" name=\"purchaserEmail\" value=\"" . $this->purchaser['email'] . "\"/>");
        print("<input type=\"hidden\" name=\"successUrl\" value=\"" . $this->success_url . "\"/>");
        print("<input type=\"hidden\" name=\"failureUrl\" value=\"" . $this->failure_url . "\"/>");
        print("<input type=\"hidden\" name=\"echo\" value=\"" . $this->echo . "\"/>");
        print("<input type=\"hidden\" name=\"version\" value=\"" . $this->version . "\"/>");
        print("<input type=\"hidden\" name=\"transactionDate\" value=\"" . $timestamp . "\"/>");
        print("<input type=\"hidden\" name=\"token\" value=\"" . $token . "\"/>");
        print("<input type=\"submit\" value=\"Öde\" style=\"display:none;\"/>");
        print("<noscript>");
        print("<br/>");
        print("<br/>");
        print("<center>");
        print("<h1>3D Secure Yönlendirme İşlemi</h1>");
        print("<h2>Javascript internet tarayıcınızda kapatılmış veya desteklenmiyor.<br/></h2>");
        print("<h3>Lütfen banka 3D Secure sayfasına yönlenmek için tıklayınız.</h3>");
        print("<input type=\"submit\" value=\"3D Secure Sayfasına Yönlen\">");
        print("</center>");
        print("</noscript>");
        print("</form>");
        print("</body>");
        print("<script>document.getElementById(\"three_d_form\").submit();</script>");
        print("</html>");
    }

    private function get_client_ip()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }

    private function prepareXML()
    {
        $xml_data_product_part = "";
        foreach ($this->products as $product) {
            $xml_data_product_part .= "<product>\n" .
                "	<productCode>" . $product['code'] . "</productCode>\n" .
                "	<productName>" . $product['title'] . "</productName>\n" .
                "	<quantity>" . $product['quantity'] . "</quantity>\n" .
                "	<price>" . number_format((float)$product['price'], 2, '', '') . "</price>\n" .
                "</product>\n";
        }

        $three_d_secure_code_part = "";
        if ($this->three_d == "true") {
            $three_d_secure_code_part = "    <threeDSecureCode>" . $this->three_d_secure_code . "</threeDSecureCode>\n";
        }

        $vendor_id_part = "    <vendorId>4</vendorId>\n";

        $xml_data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
            "<auth>\n" .
            "    <threeD>" . $this->three_d . "</threeD>\n" .
            "    <orderId>" . $this->order_id . "</orderId>\n" .
            "    <amount>" . number_format((float)$this->amount, 2, '', '') . "</amount>\n" .
            "    <cardOwnerName>" . $this->card['owner_name'] . "</cardOwnerName>\n" .
            "    <cardNumber>" . $this->card['number'] . "</cardNumber>\n" .
            "    <cardExpireMonth>" . $this->card['expire_month'] . "</cardExpireMonth>\n" .
            "    <cardExpireYear>" . $this->card['expire_year'] . "</cardExpireYear>\n" .
            "    <installment>" . $this->installment . "</installment>\n" .
            "    <cardCvc>" . $this->card['cvc'] . "</cardCvc>\n" .
            "    <mode>" . $this->mode . "</mode>\n" .
            $three_d_secure_code_part .
            $vendor_id_part .
            "    <products>\n" .
            $xml_data_product_part .
            "    </products>\n" .
            "    <purchaser>\n" .
            "        <name>" . $this->purchaser['name'] . "</name>\n" .
            "        <surname>" . $this->purchaser['surname'] . "</surname>\n" .
            "        <birthDate>" . $this->purchaser['birthdate'] . "</birthDate>\n" .
            "        <email>" . $this->purchaser['email'] . "</email>\n" .
            "        <gsmNumber>" . $this->purchaser['gsm_number'] . "</gsmNumber>\n" .
            "        <tcCertificate>" . $this->purchaser['tc_certificate_number'] . "</tcCertificate>\n" .
            "        <clientIp>" . $this->get_client_ip() . "</clientIp>\n" .
            "        <invoiceAddress>\n" .
            "            <name>" . $this->invoice_address['name'] . "</name>\n" .
            "            <surname>" . $this->invoice_address['surname'] . "</surname>\n" .
            "            <address>" . $this->invoice_address['address'] . "</address>\n" .
            "            <zipcode>" . $this->invoice_address['zipcode'] . "</zipcode>\n" .
            "            <city>" . $this->invoice_address['city_code'] . "</city>\n" .
            "            <tcCertificate>" . $this->invoice_address['tc_certificate_number'] . "</tcCertificate>\n" .
            "            <country>" . $this->invoice_address['country_code'] . "</country>\n" .
            "            <taxNumber>" . $this->invoice_address['tax_number'] . "</taxNumber>\n" .
            "            <taxOffice>" . $this->invoice_address['tax_office'] . "</taxOffice>\n" .
            "            <companyName>" . $this->invoice_address['company_name'] . "</companyName>\n" .
            "            <phoneNumber>" . $this->invoice_address['phone_number'] . "</phoneNumber>\n" .
            "        </invoiceAddress>\n" .
            "        <shippingAddress>\n" .
            "            <name>" . $this->shipping_address['name'] . "</name>\n" .
            "            <surname>" . $this->shipping_address['surname'] . "</surname>\n" .
            "            <address>" . $this->shipping_address['address'] . "</address>\n" .
            "            <zipcode>" . $this->shipping_address['zipcode'] . "</zipcode>\n" .
            "            <city>" . $this->shipping_address['city_code'] . "</city>\n" .
            "            <country>" . $this->shipping_address['country_code'] . "</country>\n" .
            "            <phoneNumber>" . $this->shipping_address['phone_number'] . "</phoneNumber>\n" .
            "        </shippingAddress>\n" .
            "    </purchaser>\n" .
            "</auth>";
        return $xml_data;
    }

    private function prepareResponse($output)
    {
        $xml_response = new SimpleXMLElement($output);
        if ($xml_response == NULL) {
            throw new Exception("Ödeme cevabı xml formatında değil");
        }
        $response = array();
        $response['result'] = $xml_response->result;
        $response['order_id'] = $xml_response->orderId;
        $response['amount'] = $xml_response->amount;
        $response['mode'] = $xml_response->mode;
        $response['public_key'] = $xml_response->publicKey;
        $response['echo'] = $xml_response->echo;
        $response['error_code'] = $xml_response->errorCode;
        $response['error_message'] = $xml_response->errorMessage;
        $response['transaction_date'] = $xml_response->transactionDate;
        $response['hash'] = $xml_response->hash;
        return $response;
    }

    // 3D Secure Sonucunun Islenmesi ve Hash Gecerlilik Kontrolu
    public function getThreeDResponse($output)
    {
        $response = array();
        $response['result'] = $output['result'];
        $response['order_id'] = $output['orderId'];
        $response['amount'] = $output['amount'];
        $response['mode'] = $output['mode'];
        if (isset($output['publicKey'])) {
            $response['public_key'] = $output['publicKey'];
        } else {
            $response['public_key'] = "";
        }
        if (isset($output['echo'])) {
            $response['echo'] = $output['echo'];
        }
        if (isset($output['errorCode'])) {
            $response['error_code'] = $output['errorCode'];
        } else {
            $response['errorCode'] = "";
        }
        if (isset($output['errorMessage'])) {
            $response['error_message'] = $output['errorMessage'];
        } else {
            $response['error_message'] = "";
        }
        if (isset($output['transactionDate'])) {
            $response['transaction_date'] = $output['transactionDate'];
        } else {
            $response['transaction_date'] = "";
        }
        if (isset($output['hash'])) {
            $response['hash'] = $output['hash'];
        } else {
            $response['hash'] = "";
        }
        if (isset($output['threeDSecureCode'])) {
            $response['three_d_secure_code'] = $output['threeDSecureCode'];
        } else {
            $response['three_d_secure_code'] = "";
        }
        try {
            $this->validateResponse($response);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $response['amount'] = number_format((float)($response['amount'] / 100), 2, '.', '');

        return $response;
    }

    private function calliParaAuthService($xml_data)
    {
        $timestamp = date("Y-m-d H:i:s");
        $token = "";
        if ($this->three_d == "false") {
            $hash_text = $this->private_key . $this->order_id . number_format((float)$this->amount, 2, '', '') . $this->mode . $this->card['owner_name'] .
                $this->card['number'] . $this->card['expire_month'] . $this->card['expire_year'] . $this->card['cvc'] .
                $this->purchaser['name'] . $this->purchaser['surname'] . $this->purchaser['email'] . $timestamp;
            $token = $this->public_key . ":" . base64_encode(sha1($hash_text, true));
        } else if ($this->three_d == "true") {
            $hash_text = $this->private_key . $this->order_id . number_format((float)$this->amount, 2, '', '') . $this->mode .
                $this->three_d_secure_code . $timestamp;
            $token = $this->public_key . ":" . base64_encode(sha1($hash_text, true));
        }
        $ch = curl_init($this->auth_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml", "transactionDate: " . $timestamp, "version: " . $this->version, "token: " . $token));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function validateResponse($response)
    {
        if ($response['hash'] != NULL) {
            $hash_text = $response['order_id'] . $response['result'] . $response['amount'] . $response['mode'] . $response['error_code'] .
                $response['error_message'] . $response['transaction_date'] . $response['public_key'] . $this->private_key;
            $hash = base64_encode(sha1($hash_text, true));
            if ($hash != $response['hash']) {
                throw new Exception("Ödeme cevabı hash doğrulaması hatalı. [result : " . $response['result'] . ",error_code : " . $response['error_code'] . ",error_message : " . $response['error_message'] . "]");
            }
        } else {
            throw new Exception("Ödeme cevabı hash doğrulaması hatalı.");
        }
    }
}

?>