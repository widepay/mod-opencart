#  Módulo Wide Pay para Opencart
Módulo desenvolvido para integração entre o Opencart e Wide Pay. Com o módulo é possível gerar boletos para pagamento e liquidação automática pelo Wide Pay após o recebimento.

* **Versão atual:** 1.0.0
* **Versão Opencart Testada:** 3.0.2.0
* **Acesso Wide Pay**: [Abrir Link](https://www.widepay.com/acessar)
* **API Wide Pay**: [Abrir Link](https://widepay.github.io/api/index.html)
* **Módulos Wide Pay**: [Abrir Link](https://widepay.github.io/api/modulos.html)

# Instalação Plugin
**FAÇA UM BACKUP DE SEUS ARQUIVOS E BANCO DE DADOS ANTES DE INICIAR.**

1. Para a instalação do plugin realize o download pelo link: https://github.com/widepay/mod-opencart
2. Após o download concluído, acesse o servidor do opencart utilizando algum programa de FTP, como FileZilla.
3. Envie as pastas e arquivos para o servidor, seguindo o padrão das pastas.
4. Acesse a administração do Opencart.
5. Acesse o menu: Extensions -> Modifications. Clique no botão: Clear, e Refresh
6. Acesse o menu: Extensions -> Extensions -> Selecione a opção: Payments. e instale a opção Wide Pay.
7. Clique em editar para configurar o plugin. Nesta etapa siga o tutorial a baixo.

# Configuração do Plugin
Lembre-se que para esta etapa, o plugin deve estar instalado e ativado no sistema Wordpress.

A configuração do Plugin Wide Pay pode ser encontrada no menu: Wordpress -> Plugins -> Woo Wide Pay -> Configurações. Ou Wordpress -> WooCommerce -> Settings -> Checkout -> Wide Pay.




Para configuração do Wide Pay é preciso que pelo menos os 5 campos obrigatórios sejam preenchidos. Segue a lista dos campos e descrição.

|Campo|Obrigatório|Descrição|
|--- |--- |--- |
|Title|**Sim**|Nome do método de pagamento que será exibido no checkout|
|Status|**Sim**|Sim para exibir Wide Pay no checkout|
|Título|**Sim**|Título do método de pagamento que será exibido no checkout|
|Descrição|**Sim**|Descrição do método de pagamento que será exibido no checkout|
|ID da Carteira Wide Pay |**Sim** |Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras|
|Token da Carteira Wide Pay|**Sim**|Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token|
|Taxa de Variação|Não|O valor final da fatura será recalculado de acordo com este campo.|
|Tipo da Taxa de Variação|Não|O campo acima "Taxa de Variação" será aplicado de acordo com este campo.|
|Acréscimo de Dias no Vencimento|Não|Qual a quantidade de dias para o vencimento após a data da geração da fatura.|
|Configuração de Multa|Não|Configuração de multa após o vencimento, máximo 20, utilize . e não ,|
|Configuração de Juros|Não|Configuração de juros após o vencimento, máximo 20, utilize . e não ,|
|Forma de Recebimento|**Sim**|Escolha como será a forma de recebimento, **Cartão, Boleto**|
|Campos de configurações|**Sim**|É Extremamente importante que os campos de status abaixo estejam configurados corretamente.|
