<?php

require '../autoloader.php';

use OpenBoleto\Banco\Santander;
use OpenBoleto\Agente;

$sacado = new Agente('Fernando Maia', '023.434.234-34', 'ABC 302 Bloco N', '72000-000', 'Brasília', 'DF');
$cedente = new Agente('Empresa de cosméticos LTDA', '02.123.123/0001-11', 'CLS 403 Lj 23', '71000-000', 'Brasília', 'DF');

$boleto = new Santander(array(
    // Parâmetros obrigatórios
    'dataVencimento' => new DateTime('2020-09-07'),
    'valor' => 1444.2,
    'sequencial' => 57252, // Até 13 dígitos
    'sacado' => $sacado,
    'cedente' => $cedente,
    'agencia' => 4254, // Até 4 dígitos
    'carteira' => 5, // 101, 102 ou 201
    'conta' => 13002540, // Código do cedente: Até 7 dígitos
     // IOS – Seguradoras (Se 7% informar 7. Limitado a 9%)
     // Demais clientes usar 0 (zero)
    'ios' => '0', // Apenas para o Santander

    // Parâmetros recomendáveis
    //'logoPath' => 'http://empresa.com.br/logo.jpg', // Logo da sua empresa
    'contaDv' => 2,
    'agenciaDv' => 1,
    'carteiraDv' => 1,
    'descricaoDemonstrativo' => array( // Até 5
        'Compra de materiais cosméticos',
        'Compra de alicate',
    ),
    'instrucoes' => array( // Até 8
        'Após o dia 30/11 cobrar 2% de mora e 1% de juros ao dia.',
        'Não receber após o vencimento.',
    ),

    // Parâmetros opcionais
    //'resourcePath' => '../resources',
    //'moeda' => Santander::MOEDA_REAL,
    //'dataDocumento' => new DateTime(),
    //'dataProcessamento' => new DateTime(),
    //'contraApresentacao' => true,
    //'pagamentoMinimo' => 23.00,
    //'aceite' => 'N',
    'especieDoc' => 'AB01C',
    'numeroDocumento' => '27200-1/1',
    'usoBanco' => '4705670',
    //'layout' => 'layout.phtml',
    //'logoPath' => 'http://boletophp.com.br/img/opensource-55x48-t.png',
    //'sacadorAvalista' => new Agente('Antônio da Silva', '02.123.123/0001-11'),
    //'descontosAbatimentos' => 123.12,
    //'moraMulta' => 123.12,
    //'outrasDeducoes' => 123.12,
    //'outrosAcrescimos' => 123.12,
    //'valorCobrado' => 123.12,
    //'valorUnitario' => 123.12,
    //'quantidade' => 1,
));

echo $boleto->getOutput();
