<?php
/*
 * OpenBoleto - Geração de boletos bancários em PHP
 *
 * LICENSE: The MIT License (MIT)
 *
 * Copyright (C) 2013 Estrada Virtual
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace OpenBoleto\Banco;

use OpenBoleto\BoletoAbstract;
use OpenBoleto\Exception;

/**
 * Classe boleto Banco Safra
 *
 * @package    OpenBoleto
 * @author     Rodrigo Mangini
 * @copyright  Copyright (c) 2020
 * @license    MIT License
 * @version    1.0
 */
class Safra extends BoletoAbstract
{
    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = '422';

    /**
     * Localização do logotipo do banco, referente ao diretório de imagens
     * @var string
     */
    protected $logoBanco = 'safra.jpg';

    /**
     * Linha de local de pagamento
     * @var string
     */
    protected $localPagamento = 'Pagável em qualquer Banco do Sistema de Compensação';

    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = array('1', '2');

    /**
     * Campo obrigatório para emissão de boletos com carteira 198 fornecido pelo Banco com 5 dígitos
     * @var int
     */
    protected $codigoCliente;

    /**
     * Dígito verificador da carteira/nosso número para impressão no boleto
     * @var int
     */
    protected $carteiraDv;

    /**
     * Dígito de auto-conferência do nosso número
     * @var int
     */
    protected $dacNossoNumero;

    /**
     * Cache do campo livre para evitar processamento desnecessário.
     *
     * @var string
     */
    protected $campoLivre;

    /**
     * Define o código do cliente
     *
     * @param int $codigoCliente
     * @return $this
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;
        return $this;
    }

    /**
     * Retorna o código do cliente
     *
     * @return int
     */
    public function getCodigoCliente()
    {
        return $this->codigoCliente;
    }

    /**
     * Gera o Nosso Número.
     *
     * @return string
     */
    protected function gerarNossoNumero()
    {
        // $this->gerarDacNossoNumero(); // Força o calculo do DV.
        // $numero = self::zeroFill($this->getCarteira(), 3) . '/' . self::zeroFill($this->getSequencial(), 9);
        $numero = self::zeroFill($this->getSequencial(), 9);
        // $numero .= '-' . $this->dacNossoNumero;

        return $numero;
    }

    /**
     * Gera o DAC do Nosso Número
     * Anexo 4 – Cálculo do DAC do campo “Nosso Número”, em boletos emitidos pelo próprio cliente.
     * Para a grande maioria das carteiras, são considerados para a obtenção do DAC, os dados “AGÊNCIA / CONTA
     * (sem DAC) / CARTEIRA / NOSSO NÚMERO”, calculado pelo critério do Módulo 10 (conforme Anexo 3).
     * À exceção, estão as carteiras 126 - 131 - 146 - 150 e 168 cuja obtenção está baseada apenas nos dados
     * “CARTEIRA/NOSSO NÚMERO” da operação
     */
    protected function gerarDacNossoNumero()
    {
        $carteira = self::zeroFill($this->getCarteira(), 1);
        $sequencial = self::zeroFill($this->getSequencial(), 9);
        if (in_array($this->getCarteira(), array('1'))) {
            $this->dacNossoNumero = static::modulo10($this->getSequencial());
        } else {
            $agencia = self::zeroFill($this->getAgencia(), 5);
            $conta = self::zeroFill($this->getConta(), 8);
            $this->dacNossoNumero = static::modulo10($agencia . $conta . $carteira . $sequencial);
        }
    }

    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \OpenBoleto\Exception
     */
    public function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        // $sequencial = self::zeroFill($this->getSequencial(), 9);
        // $carteira = self::zeroFill($this->getCarteira(), 1);
        $agencia = self::zeroFill($this->getAgencia(), 5);
        $conta = self::zeroFill($this->getConta(), 8);

        // Módulo 10 Agência/Conta
        $dvAgConta = static::modulo10($this->codigoBanco . $this->moeda . '7' . substr($agencia,0,4));
        $dvConta   = static::modulo10(substr($agencia, 4, 1) . $conta . $this->getContaDv());

        // $this->carteiraDv = $dvAgContaCarteira = static::modulo10($carteira . $sequencial);
        return $this->campoLivre = substr($agencia, 0, 4) . $dvAgConta . substr($agencia, 4, 1) . $conta . $this->getContaDv() . $dvConta;
    }

    /**
     * Retorna a linha digitável do boleto
     *
     * @return string
     */
    public function getLinhaDigitavel()
    {
        $chave = $this->getCampoLivre();

        // Concatenates bankCode + currencyCode + first block of 5 characters and
        // calculates its check digit for part1.
        // $check_digit = static::modulo10($this->getCodigoBanco() . $this->getMoeda());
        $check_digit = 7;

        // Shift in a dot on block 20-24 (5 characters) at its 2nd position.
        // $blocks['20-24'] = substr_replace($blocks['20-24'], '.', 1, 0);

        // Concatenates bankCode + currencyCode + first block of 5 characters +
        // checkDigit.
        $part1 = $this->getCodigoBanco() . $this->getMoeda() . $check_digit;

        $part2 = substr($chave, 0, 5) . ' ' . substr($chave, 5, 5) . '.' . substr($chave, -6);

        // As part2, we do the same process again for part3.
        $part3 = $sequencial = self::zeroFill($this->getSequencial(), 9) . '2'; // 2 fixo
        $check_digit = static::modulo10($part3);
        $part3 = substr($part3,0, 5) . '.' . substr($part3, -5) . $check_digit;

        $this->gerarDacNossoNumero();
        $cd = $this->dacNossoNumero;

        $part4  = $this->getFatorVencimento() . $this->getValorZeroFill();

        // Now put everything together.
        return "$part1.$part2 $part3 $cd $part4";
    }

    /**
     * Define nomes de campos específicos do boleto do Itaú
     *
     * @return array
     */
    public function getViewVars()
    {
        return array(
            'carteira' => $this->getCarteira(), // Campo não utilizado pelo Itaú
        );
    }

    public function setEspecieDoc($especieDoc)
    {
        $especie = [
            '01' => 'DM',
            '02' => 'NP',
            '03' => 'NS',
            '05' => 'RC',
            '09' => 'DS',
            '99' => 'OU',
        ];
        $this->especieDoc = $especie[$especieDoc];
        return $this;
    }

}
