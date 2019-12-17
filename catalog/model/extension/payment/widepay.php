<?php
/*
* @package    Wide Pay
* @version    1.0
* @license    BSD License
* @copyright  (c) 2019
* @link       https://widepay.com/
* @dev        Gabriel Pasche
*/
class ModelExtensionPaymentWidePay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/widepay');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_widepay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('payment_widepay_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'widepay',
				'title'      => $this->config->get('payment_widepay_title'),
				//'title'      => "<img src='caminho de sua imagem'>",
				'terms'      => '',
				'sort_order' => $this->config->get('payment_widepay_sort_order')
			);
		}

		return $method_data;
	}
}
