<?php /** @noinspection ALL */

/*
* @package    Wide Pay
* @version    1.0
* @license    BSD License
* @copyright  (c) 2019
* @link       https://widepay.com/
* @dev        Gabriel Pasche
*/

class ControllerExtensionPaymentWidePay extends Controller
{
    private $default_values = array(
        'payment_widepay_title' => 'Wide Pay',
        'payment_widepay_description' => 'Pagar com Wide Pay',
//        'payment_widepay_item_name' => 'Fatura referente ao pedido: {id_fatura}.',
        'payment_widepay_tax' => '0',
        'payment_widepay_tax_type' => '1',
        'payment_widepay_plus_date_due' => '7',
        'payment_widepay_fine' => '0',
        'payment_widepay_interest' => '0',
        'payment_widepay_way' => 'cartao,boleto',
    );
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/widepay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_widepay', $this->request->post);
            $this->response->redirect($this->url->link('extension/payment/widepay', 'salvo=true&user_token=' . $this->session->data['user_token'], true));
        }

        $data['campos'] = $this->getOpencartCustomFields();

        $data = array_merge($data, $this->language->load('extension/payment/widepay'));
        $data = array_merge($data, $this->error);


        $data['salvo_com_sucesso'] = isset($_GET['salvo']) ? true : false;


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'type=payment&user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/widepay', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/widepay', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'type=payment&user_token=' . $this->session->data['user_token'], true);

        $fields = array(
            'payment_widepay_title',
            'payment_widepay_description',
//            'payment_widepay_item_name',
            'payment_widepay_wallet_id',
            'payment_widepay_wallet_token',
            'payment_widepay_tax',
            'payment_widepay_tax_type',
            'payment_widepay_plus_date_due',
            'payment_widepay_fine',
            'payment_widepay_interest',
            'payment_widepay_way',
            'payment_widepay_status',
            'payment_widepay_cpf_field',
            'payment_widepay_cnpj_field',
            'payment_widepay_number_field',
            'payment_widepay_complement_field',
            'payment_widepay_status_waiting_payment',
            'payment_widepay_status_payed',
            'payment_widepay_status_cancelled',
            'payment_widepay_geo_zone_id',
            'payment_widepay_sort_order',
        );
        $data = array_merge($data, $this->getFieldsValues($fields));
        $data['payment_widepay_tax_type_select'] = array(
            '1' => 'Acrécimo em %',
            '2' => 'Acrécimo valor fixo em R$',
            '3' => 'Desconto em %',
            '4' => 'Desconto valor fixo em R$'
        );
        $data['payment_widepay_way_select'] = array(
            'cartao' => 'Cartão',
            'boleto' => 'Boleto Bancário',
            'cartao,boleto' => 'Boleto Bancário e Cartão',
        );


        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/widepay', $data));
    }

    protected function validate()
    {
        $required_fields = [
            'payment_widepay_title',
            'payment_widepay_description',
//            'payment_widepay_item_name',
            'payment_widepay_wallet_id',
            'payment_widepay_wallet_token',
            'payment_widepay_plus_date_due',
            'payment_widepay_fine',
            'payment_widepay_interest',
        ];

        if (!$this->user->hasPermission('modify', 'extension/payment/widepay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }



        if (!is_numeric($this->request->post['payment_widepay_plus_date_due'])) {
            $this->error['error_payment_widepay_plus_date_due'] = 'Infome o prazo de validade em dias!';
        } else {
            $this->request->post['payment_widepay_plus_date_due'] = (int)$this->request->post['payment_widepay_plus_date_due'];
        }


        if (!is_numeric($this->request->post['payment_widepay_fine'])) {
            $this->error['error_payment_widepay_fine'] = 'Utilize . no lugar da , exemplo: 1.7 ';
        } else if ($this->request->post['payment_widepay_fine'] == 0) {
            unset($required_fields[array_search('payment_widepay_fine',$required_fields)]);
        } else {
            unset($required_fields[array_search('payment_widepay_fine',$required_fields)]);
            $this->request->post['payment_widepay_fine'] = (float)$this->request->post['payment_widepay_fine'];
        }

        if (!is_numeric($this->request->post['payment_widepay_interest'])) {
            $this->error['error_payment_widepay_interest'] = 'Utilize . no lugar da , exemplo: 1.7 ';
        } else if ($this->request->post['payment_widepay_interest'] == 0) {
            unset($required_fields[array_search('payment_widepay_interest',$required_fields)]);
        } else {
            unset($required_fields[array_search('payment_widepay_interest',$required_fields)]);
            $this->request->post['payment_widepay_interest'] = (float)$this->request->post['payment_widepay_interest'];
        }


        foreach ($required_fields as $field) {
            if (!$this->request->post[$field]) {
                $this->error['error_' . $field] = 'Preencha os campos obrigatórios';
            }
        }


        return !$this->error;
    }

    public function getOpencartCustomFields($data = array())
    {
        if (empty($data['filter_customer_group_id'])) {
            $sql = "SELECT * FROM `" . DB_PREFIX . "custom_field` cf LEFT JOIN " . DB_PREFIX . "custom_field_description cfd ON (cf.custom_field_id = cfd.custom_field_id) WHERE cfd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        } else {
            $sql = "SELECT * FROM " . DB_PREFIX . "custom_field_customer_group cfcg LEFT JOIN `" . DB_PREFIX . "custom_field` cf ON (cfcg.custom_field_id = cf.custom_field_id) LEFT JOIN " . DB_PREFIX . "custom_field_description cfd ON (cf.custom_field_id = cfd.custom_field_id) WHERE cfd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND cfd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_customer_group_id'])) {
            $sql .= " AND cfcg.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
        }

        $sort_data = array(
            'cfd.name',
            'cf.type',
            'cf.location',
            'cf.status',
            'cf.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY cfd.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function getFieldsValues($fields)
    {
        $data = [];
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } else if ($this->config->get($field) != null) {
                $data[$field] = $this->config->get($field);
            } else if (isset($this->default_values[$field])) {
                $data[$field] = $this->default_values[$field];
            } else {
                $data[$field] = '';
            }
        }
        return $data;
    }

}

?>
