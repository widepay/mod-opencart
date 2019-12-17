<?php

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

    public function index()
    {
        $this->language->load('extension/payment/widepay');

        $getCpfOrCnpj = function () {
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            $fiscal = '';
            $cpf = $this->config->get('payment_widepay_origem_cpf');
            $cnpj = $this->config->get('payment_widepay_origem_cnpj');
            if (isset($order_info['custom_field'][$cpf]) && !empty($order_info['custom_field'][$cpf])) {
                $fiscal = preg_replace('/\D/', '', $order_info['custom_field'][$cpf]);
            } elseif (isset($order_info['custom_field'][$cnpj]) && !empty($order_info['custom_field'][$cnpj])) {
                $fiscal = preg_replace('/\D/', '', $order_info['custom_field'][$cnpj]);
            }
            return $fiscal;
        };

        $data['cnpj_cpf'] = $getCpfOrCnpj();
        $data['id_pedido'] = $this->session->data['order_id'];
        $data['continue'] = $this->url->link('checkout/success', '', 'SSL');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        return $this->load->view('extension/payment/widepay', $data);
    }

    public function getDescontos()
    {
        $query = $this->db->query("SELECT SUM(value) AS desconto FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$this->session->data['order_id'] . "' AND value < 0");
        if (!isset($query->row['desconto'])) {
            return 0;
        }
        $num = $query->row['desconto'];
        $num = $num <= 0 ? $num : -$num;
        return abs($num);
    }

    public function getFrete()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$this->session->data['order_id'] . "' AND code = 'shipping'");
        if (!isset($query->row['value'])) {
            return 0;
        }
        return abs($query->row['value']);
    }

    public function getTaxas()
    {
        $query = $this->db->query("SELECT SUM(value) AS taxa FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$this->session->data['order_id'] . "' AND code = 'tax'");
        if (isset($query->row['taxa'])) {
            return abs($query->row['taxa']);
        } else {
            return 0;
        }
    }

    public function response()
    {
        $this->load->model('checkout/order');

        @ob_clean();
        header('HTTP/1.1 200 OK');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["notificacao"])) {
            $notificacao = $this->api(intval($this->config->get('payment_widepay_wallet_id')), trim($this->config->get('payment_widepay_wallet_token')), 'recebimentos/cobrancas/notificacao', array(
                'id' => $_POST["notificacao"] // ID da notificação recebido do Wide Pay via POST
            ));
            if ($notificacao->sucesso) {
                $order_id = (int)$notificacao->cobranca['referencia'];
                $transactionID = $notificacao->cobranca['id'];
                $status = $notificacao->cobranca['status'];
                if ($status == 'Baixado' || $status == 'Recebido' || $status == 'Recebido manualmente') {
                    $pedido = $this->model_checkout_order->getOrder($order_id);
                    if ($pedido['order_status_id'] != $this->config->get('payment_widepay_status_payed')) {
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_widepay_status_payed'), '', true);
                    }
                    echo 'Pagamento atualizado';
                } else {
                    echo 'Status não suportado';
                }
                exit();
            } else {
                echo $notificacao->erro; // Erro
                exit();
            }
        }
        exit();
    }

    private function generate()
    {
        $this->load->model('checkout/order');
        $pedidos = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        //custom
        $numero = $this->config->get('payment_widepay_number_field');
        $complemento = $this->config->get('payment_widepay_complement_field');
        $tax = $this->config->get('payment_widepay_tax');
        $tax_type = $this->config->get('payment_widepay_tax_type');


        //produtos
        $items = [];
        $i = 1;
        foreach ($this->cart->getProducts() AS $produto) {
            $items[$i]['descricao'] = $produto['name'];
            $items[$i]['valor'] = number_format($produto['price'], 2, '.', '');
            $items[$i]['quantidade'] = $produto['quantity'];
            $i++;
        }
        $shipping = $this->getFrete();
        if (isset($shipping) && $shipping > 0) {
            $items[$i]['descricao'] = 'Frete';
            $items[$i]['valor'] = number_format($shipping, 2, '.', '');
            $items[$i]['quantidade'] = 1;
            $i++;
        }
        $discount = $this->getDescontos();
        if (isset($discount) && $discount > 0) {
            $items[$i]['descricao'] = 'Desconto';
            $items[$i]['valor'] = number_format($discount, 2, '.', '') * (-1);
            $items[$i]['quantidade'] = 1;
            $i++;
        }
        $variableTax = $this->getVariableTax($tax, $tax_type, $this->cart->getTotal());
        if (isset($variableTax)) {
            list($description, $total) = $variableTax;
            $items[$i]['descricao'] = $description;
            $items[$i]['valor'] = $total;
            $items[$i]['quantidade'] = 1;
            $i++;
        }
        $taxas = $this->getTaxas();
        if ($taxas > 0) {
            $items[$i]['descricao'] = 'Taxas e impostos';
            $items[$i]['valor'] = number_format($taxas, 2, '.', '');
            $items[$i]['quantidade'] = 1;
        }


        //////------


        $invoiceDuedate = new DateTime(date('Y-m-d'));
        $invoiceDuedate->modify('+' . intval($this->config->get('payment_widepay_plus_date_due')) . ' day');
        $invoiceDuedate = $invoiceDuedate->format('Y-m-d');


        $cpf_cnpj = isset($_GET['cnpj_cpf']) ? $_GET['cnpj_cpf'] : '';
        list($widepayCpf, $widepayCnpj, $widepayPessoa) = $this->getFiscal($cpf_cnpj);

        $widepayData = array(
            'forma' => $this->widepay_get_formatted_way(trim($this->config->get('payment_widepay_way'))),
            'referencia' => $this->session->data['order_id'],
            'notificacao' => $this->url->link('extension/payment/widepay/response', '', 'SSL'),
            'vencimento' => $invoiceDuedate,
            'cliente' => $pedidos['payment_firstname'] . ' ' . $pedidos['payment_lastname'],
            'telefone' => preg_replace('/\D/', '', $pedidos['telephone']),
            'email' => $pedidos['email'],
            'pessoa' => $widepayPessoa,
            'cpf' => $widepayCpf,
            'cnpj' => $widepayCnpj,
            'endereco' => array(
                'rua' => $pedidos['payment_address_1'] . ', ' . $numero,
                'complemento' => $pedidos['payment_address_2'] . $complemento,
                'cep' => preg_replace('/\D/', '', $pedidos['payment_postcode']),
                'estado' => $pedidos['payment_zone_code'],
                'cidade' => $pedidos['payment_city']
            ),
            'itens' => $items,
            'boleto' => array(
                'gerar' => 'Nao',
                'desconto' => 0,
                'multa' => doubleval($this->config->get('payment_widepay_fine')),
                'juros' => doubleval($this->config->get('payment_widepay_interest')
                )
            ));

        return $this->api(intval($this->config->get('payment_widepay_wallet_id')), trim($this->config->get('payment_widepay_wallet_token')), 'recebimentos/cobrancas/adicionar', $widepayData);
    }

    public function confirm()
    {
        $this->load->model('checkout/order');
        $json = array();
        $response = $this->generate();

        if (!$response->sucesso) {
            $json['success'] = false;
            $validacao = '';

            if ($response->erro) {
                $this->log->write('Wide Pay: Erro (' . $response->erro . ')');
                $this->log->write(print_r($response, true));
                $json['error'] = 'Erro Wide Pay: ' . $response->erro;
            }

            if (isset($response->validacao)) {
                foreach ($response->validacao as $item) {
                    $validacao .= '- ' . strtoupper($item['id']) . ': ' . $item['erro'] . '<br>';
                }
                $this->log->write('Wide Pay: Erro de validação (' . $validacao . ')');
                $this->log->write(print_r($response, true));
                $json['error'] = 'Erro Validação: ' . $validacao;
            }

        } else {
            $msg = "Transação " . $response->id . " - <a href='" . $response->link . "' target='_blank'>Pagar Fatura</a>";
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_widepay_status_waiting_payment'), $msg, true);

            $json['success'] = true;
            $json['link'] = $response->link;
            $json['public_id'] = $response->id;
        }

        echo json_encode($json);
    }


    private function api($wallet, $token, $local, $params = array())
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.widepay.com/v1/' . trim($local, '/'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, trim($wallet) . ':' . trim($token));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('WP-API: SDK-PHP'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        $exec = curl_exec($curl);
        curl_close($curl);
        if ($exec) {
            $requisicao = json_decode($exec, true);
            if (!is_array($requisicao)) {
                $requisicao = array(
                    'sucesso' => false,
                    'erro' => 'Não foi possível tratar o retorno.'
                );
                if ($exec) {
                    $requisicao['retorno'] = $exec;
                }
            }
        } else {
            $requisicao = array(
                'sucesso' => false,
                'erro' => 'Sem comunicação com o servidor.'
            );
        }

        return (object)$requisicao;
    }

    private function widepay_get_formatted_way($way)
    {
        $key_value = array(
            'cartao' => 'Cartão',
            'boleto' => 'Boleto',
            'cartao,boleto' => 'Cartão,Boleto',

        );
        return $key_value[$way];
    }

    private function getFiscal($cpf_cnpj)
    {
        $cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);
        // [CPF, CNPJ, FISICA/JURIDICA]
        if (strlen($cpf_cnpj) == 11) {
            return array($cpf_cnpj, '', 'Física');
        } else {
            return array('', $cpf_cnpj, 'Jurídica');
        }
    }

    private function getVariableTax($tax, $taxType, $total)
    {
        //Formatação para calculo ou exibição na descrição
        $widepayTaxDouble = number_format((double)$tax, 2, '.', '');
        $widepayTaxReal = number_format((double)$tax, 2, ',', '');
        // ['Description', 'Value'] || Null

        if ($taxType == 1) {//Acrécimo em Porcentagem
            return array(
                'Referente a taxa adicional de ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2));
        } elseif ($taxType == 2) {//Acrécimo valor Fixo
            return array(
                'Referente a taxa adicional de R$' . $widepayTaxReal,
                ((double)$widepayTaxDouble));
        } elseif ($taxType == 3) {//Desconto em Porcentagem
            return array(
                'Item referente ao desconto: ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2) * (-1));
        } elseif ($taxType == 4) {//Desconto valor Fixo
            return array(
                'Item referente ao desconto: R$' . $widepayTaxReal,
                $widepayTaxDouble * (-1));
        }
        return null;
    }
}

?>
