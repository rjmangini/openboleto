<?php

require '../autoloader.php';

use OpenBoleto\Banco\Santander;
use OpenBoleto\Agente;

$sacado = new Agente('Fernando Maia', '023.434.234-34', 'ABC 302 Bloco N', '72000-000', 'Brasília', 'DF');
$cedente = new Agente('Empresa de cosméticos LTDA', '18361518000116', 'CLS 403 Lj 23', '71000-000', 'Brasília', 'DF');

$boleto = [
    'vencimento' => '2020-09-08',
    'valor' => 1039.8,
    'seq_reg_remessa' => 655,
    'agencia' => '4254',
    'agencia_dv' => null,
    'numero' => '13002540',
    'numero_dv' => '6',
    'num_carteira' => 5,
    'documento' => '27200-1/1',
    'uso_empresa' => '57252',
    'aceite' => 'N',
    'uso_banco' => '4705670',
    'especie' => '01',
];
$multaDiaria = 1.23;
$multaAtraso = 12.34;
$nfe = '27200';

$boletoPdf = new Santander(array(
    // Parâmetros obrigatórios
    'dataVencimento' => new \DateTime($boleto['vencimento']),
    'valor' => $boleto['valor'],
    'sequencial' => $boleto['seq_reg_remessa'],
    'sacado' => $sacado,
    'cedente' => $cedente,
    'agencia' => $boleto['agencia'],
    'agenciaDv' => $boleto['agencia_dv'],
    'conta' => $boleto['uso_banco'],
    //'contaDv' => $boleto['numero_dv'],
    'carteira' => $boleto['num_carteira'],
    'numeroDocumento' => $boleto['documento'],
    'descricaoDemonstrativo' => array( // Até 5
    ),
    'instrucoes' => array( // Até 8
        'APOS VENCIMENTO COBRAR R$ ' . $multaDiaria . ' POR DIA DE ATRASO',
        'APOS VENCIMENTO COBRAR MULTA DE R$ ' . $multaAtraso,
        'PRODUTOS "DYSTRAY"',
        'NUMERO INTERNO: ' . $boleto['uso_empresa'] . ' / DANFE: ' . $nfe,
        'APOS 5 DIAS DO VENCIMENTO O TITULO SERA PROTESTADO / NEGATIVADO',
        'COBRANCA (11) 5510-5050 - WHATSAPP (11) 93293-8660',
    ),
    'moeda' => Santander::MOEDA_REAL,
    'dataDocumento' => new \DateTime(),
    'dataProcessamento' => new \DateTime(),
    'aceite' => $boleto['aceite'],
    'especieDoc' => $boleto['especie'],
    'usoBanco' => $boleto['uso_banco'],
));

echo $boletoPdf->getOutput();
