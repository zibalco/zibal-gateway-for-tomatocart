<?php

class osC_Payment_Zibal extends osC_Payment
{
    var $_title, $_code = 'zibal', $_status = false, $_sort_order, $_order_id, $_hash_id;

    function osC_Payment_Zibal()
    {
        global $osC_Database, $osC_Language, $osC_ShoppingCart;

        $this->_title = $osC_Language->get('payment_zibal_title');
        $this->_method_title = $osC_Language->get('payment_zibal_method_title');
        $this->_status = (MODULE_PAYMENT_ZIBAL_STATUS == '1') ? true : false;
        $this->_sort_order = MODULE_PAYMENT_ZIBAL_SORT_ORDER;

        $this->form_action_url = osC_href_link(FILENAME_CHECKOUT, 'process&cmd=send', 'SSL', null, null, true);

        if ($this->_status === true) {

            if ((int)MODULE_PAYMENT_ZIBAL_ORDER_STATUS_ID > 0) {

                $this->order_status = MODULE_PAYMENT_ZIBAL_ORDER_STATUS_ID;
            }

            if ((int)MODULE_PAYMENT_ZIBAL_ZONE > 0) {

                $check_flag = false;

                $Qcheck = $osC_Database->query('SELECT zone_id from :table_zones_to_geo_zones WHERE geo_zone_id = :geo_zone_id AND zone_country_id = :zone_country_id ORDER BY zone_id');

                $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
                $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_ZIBAL_ZONE);
                $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
                $Qcheck->execute();

                while ($Qcheck->next()) {

                    if ($Qcheck->valueInt('zone_id') < 1) {

                        $check_flag = true;
                        break;

                    } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {

                        $check_flag = true;
                        break;
                    }
                }

                if ($check_flag === false) {

                    $this->_status = false;
                }
            }
        }
    }

    function selection()
    {
        return [
            'id'     => $this->_code,
            'module' => $this->_method_title
        ];
    }

    function pre_confirmation_check()
    {
        return false;
    }

    function confirmation()
    {
        global $osC_Language;

        $this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);

        $confirmation = [
            'title'  => $this->_method_title,
            'fields' => [['title' => $osC_Language->get('payment_zibal_description')]]
        ];

        return $confirmation;
    }

    function process_button()
    {
        $process_button_string = osC_draw_hidden_field('order', $this->_order_id);

        return $process_button_string;
    }

    function get_error()
    {
        return false;
    }

    /**
     * connects to zibal's rest api
     * @param $path
     * @param $parameters
     * @return stdClass
     */
    function postToZibal($path, $parameters)
    {
        $url = 'https://gateway.zibal.ir/v1/'.$path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

    function process()
    {
        // echo "POST: ";
        // echo "<pre>";
        // var_dump($_POST);
        // echo "</pre>";

        // echo "<pre>";
        // var_dump($_GET);
        // die();

        global $osC_Currencies, $osC_Database, $osC_ShoppingCart, $osC_Language, $messageStack;

        $this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);
        // $this->_hash_id = '4d8c7ee7d12903b4436cb116861d6043';
        $this->_hash_id = '4d8c7ba7d12903b4436cb116861d6092';

        if (MODULE_PAYMENT_ZIBAL_CURRENCY == 'Selected Currency') {

            $currency = $osC_Currencies->getCode();

        } else {

            $currency = MODULE_PAYMENT_ZIBAL_CURRENCY;
        }
        $mobile = $osC_ShoppingCart->_shipping_address['telephone_number'];

        if (isset($_GET['cmd']) && $_GET['cmd'] == 'send') {

            $order_id = isset($_POST['order']) ? $_POST['order'] : null;            

            if (isset($this->_order_id) && $this->_order_id == $order_id) {

                if (extension_loaded('curl')) {

                    $parameters = array(
                        'merchant'          => MODULE_PAYMENT_ZIBAL_API,
                        'amount'            => (string) round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2),
                        'callbackUrl'       => urlencode(osC_href_link(FILENAME_CHECKOUT, 'process=1&cmd=verify', 'SSL', null, null, true)),
                        'orderId'           => $this->_order_id,
                    );

                    // echo "<pre>";
                    // var_dump($parameters);
                    // die();

                    $result = $this->postToZibal('request', $parameters);

                    if (isset($result->result) && $result->result == 100) {

						$_SESSION['pid'] = $result->result;
                        $Qtransaction = $osC_Database->query('INSERT INTO :table_online_transactions (orders_id, receipt_id, transaction_method, transaction_date, transaction_amount) VALUES (:orders_id, :receipt_id, :transaction_method, now(), :transaction_amount)');

                        $Qtransaction->bindTable(':table_online_transactions', TABLE_ONLINE_TRANSACTIONS);
                        $Qtransaction->bindInt(':orders_id', $this->_order_id);
                        $Qtransaction->bindValue(':receipt_id', $result->trackId);
                        $Qtransaction->bindValue(':transaction_method', $this->_code);
                        $Qtransaction->bindValue(':transaction_amount', round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2));
                        $Qtransaction->execute();

                        osC_redirect(osC_href_link(MODULE_PAYMENT_ZIBAL_GATEWAYURL . $result->trackId, null, null, null, true));

                    } else {

                        $errorCode = isset($result->result) ? $result->result : 'Undefined';
                        $errorMessage = isset($result->message) ? $result->message : $osC_Language->get('payment_zibal_undefined');

                        $messageStack->add_session('checkout', $osC_Language->get('payment_zibal_request_error') . '<br/><br/>' . $osC_Language->get('payment_zibal_error_code') . $errorCode . '<br/>' . $osC_Language->get('payment_zibal_error_message') . $errorMessage, 'error');

                        osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));
                    }

                } else {

                    $messageStack->add_session('checkout', $osC_Language->get('payment_zibal_curl'), 'error');

                    osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));
                }

            } else {

                $messageStack->add_session('checkout', $osC_Language->get('payment_zibal_something_wrong'), 'error');

                osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
            }

        } elseif (isset($_GET['cmd']) && $_GET['cmd'] == 'verify') {

                $token = $_GET['token'];
                $message = $_GET['message'];
				
                $parameters = array(
					'merchant'   => MODULE_PAYMENT_ZIBAL_API,
					'trackId' => $_GET['trackId']
				);

                $result = $this->postToZibal('verify', $parameters);

                $transId = isset($_GET['trackId']) ? $_GET['trackId'] : null;

				if (isset($result->result) && $result->result == 100) {

					$amount = round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2);

					if ($amount == $result->amount) {

						$Qupdate = $osC_Database->query('UPDATE :table_online_transactions SET transaction_id = :transaction_id, transaction_date = now() WHERE orders_id = :orders_id AND receipt_id = :receipt_id');

						$Qupdate->bindTable(':table_online_transactions', TABLE_ONLINE_TRANSACTIONS);
						$Qupdate->bindValue(':transaction_id', $transId);
						$Qupdate->bindInt(':orders_id', $this->_order_id);
						$Qupdate->bindValue(':receipt_id', $transId);
						$Qupdate->execute();

						$Qtransaction = $osC_Database->query('INSERT INTO :table_orders_transactions_history (orders_id, transaction_code, transaction_return_value, transaction_return_status, date_added) VALUES (:orders_id, :transaction_code, :transaction_return_value, :transaction_return_status, now())');

						$Qtransaction->bindTable(':table_orders_transactions_history', TABLE_ORDERS_TRANSACTIONS_HISTORY);
						$Qtransaction->bindInt(':orders_id', $this->_order_id);
						$Qtransaction->bindInt(':transaction_code', 1);
						$Qtransaction->bindValue(':transaction_return_value', $transId);
						$Qtransaction->bindInt(':transaction_return_status', 1);
						$Qtransaction->execute();

						$comments = $osC_Language->get('payment_zibal_method_authority') . '[' . $transId . ']';

						osC_Order::process($this->_order_id, $this->order_status, $comments);

					} else {

						$messageStack->add_session('checkout', $osC_Language->get('payment_zibal_invalid_amount'), 'error');

						osC_Order::remove($this->_order_id);
						osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
					}

				} else {

                    $resultCode = isset($result->result) ? $result->result : 'Undefined';
					$resultMessage = isset($result->message) ? $result->message : $osC_Language->get('payment_zibal_undefined');
                    if (isset($_GET['status'])) {
                        $status = $_GET['status'];
                        $statusMessage = $this->statusCodes($status);
                    } else {
                        $statusMessage = '';
                    }

					$messageStack->add_session('checkout', $osC_Language->get('payment_zibal_verify_error') . '<br/><br/>' . $osC_Language->get('payment_zibal_error_code') . $resultCode . '<br/>' . $osC_Language->get('payment_zibal_error_message') . $resultMessage . ' ' . $statusMessage, 'error');

					osC_Order::remove($this->_order_id);
					osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
				}

        } else {

            osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
        }
    }

    function callback()
    {
        global $osC_Database;
    }

    function common($url, $parameters)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }


    /**
     * returns a string message based on result parameter from curl response
     * @param $code
     * @return String
     */  
    function resultCodes($code)
    {
        switch ($code) 
        {
            case 100:
                return "با موفقیت تایید شد";
            
            case 102:
                return "merchant یافت نشد";

            case 103:
                return "merchant غیرفعال";

            case 104:
                return "merchant نامعتبر";

            case 201:
                return "قبلا تایید شده";
            
            case 105:
                return "amount بایستی بزرگتر از 1,000 ریال باشد";

            case 106:
                return "callbackUrl نامعتبر می‌باشد. (شروع با http و یا https)";

            case 113:
                return "amount مبلغ تراکنش از سقف میزان تراکنش بیشتر است.";

            case 201:
                return "قبلا تایید شده";
            
            case 202:
                return "سفارش پرداخت نشده یا ناموفق بوده است";

            case 203:
                return "trackId نامعتبر می‌باشد";

            default:
                return "وضعیت مشخص شده معتبر نیست";
        }
    }

    /**
     * returns a string message based on status parameter from $_GET
     * @param $code
     * @return String
     */
    function statusCodes($code)
    {
        switch ($code) 
        {
            case -1:
                return "در انتظار پردخت";
            
            case -2:
                return "خطای داخلی";

            case 1:
                return "پرداخت شده - تاییدشده";

            case 2:
                return "پرداخت شده - تاییدنشده";

            case 3:
                return "لغوشده توسط کاربر";
            
            case 4:
                return "‌شماره کارت نامعتبر می‌باشد";

            case 5:
                return "‌موجودی حساب کافی نمی‌باشد";

            case 6:
                return "رمز واردشده اشتباه می‌باشد";

            case 7:
                return "‌تعداد درخواست‌ها بیش از حد مجاز می‌باشد";
            
            case 8:
                return "‌تعداد پرداخت اینترنتی روزانه بیش از حد مجاز می‌باشد";

            case 9:
                return "مبلغ پرداخت اینترنتی روزانه بیش از حد مجاز می‌باشد";

            case 10:
                return "‌صادرکننده‌ی کارت نامعتبر می‌باشد";
            
            case 11:
                return "خطای سوییچ";

            case 12:
                return "کارت قابل دسترسی نمی‌باشد";

            default:
                return "وضعیت مشخص شده معتبر نیست";
        }
    }
}