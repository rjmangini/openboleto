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
 * Classe boleto Santander
 *
 * @package    OpenBoleto
 * @author     Daniel Garajau <http://github.com/kriansa>
 * @copyright  Copyright (c) 2013 Estrada Virtual (http://www.estradavirtual.com.br)
 * @license    MIT License
 * @version    1.0
 */
class Santander extends BoletoAbstract
{
    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = '033';

    /**
     * Localização do logotipo do banco, referente ao diretório de imagens
     * @var string
     */
    protected $logoBanco = 'santander.jpg';

    /**
     * Linha de local de pagamento
     * @var string
     */
    protected $localPagamento = 'Pagar preferencialmente no Banco Santander';

    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = array('1', '3', '5', '6', '7');

    /**
     * Define os nomes das carteiras para exibição no boleto
     * @var array
     */
    protected $carteirasNomes = array('101' => 'Cobrança Simples ECR', '102' => 'Cobrança Simples CSR');

    /**
     * Define o valor do IOS - Seguradoras (Se 7% informar 7. Limitado a 9%) - Demais clientes usar 0 (zero)
     * @var int
     */
    protected $ios;


    /**
     * Define o valor do IOS
     *
     * @param int $ios
     */
    public function setIos($ios)
    {
        $this->ios = $ios;
    }

    /**
     * Retorna o atual valor do IOS
     *
     * @return int
     */
    public function getIos()
    {
        return $this->ios;
    }

    /**
     * Gera o Nosso Número.
     *
     * @return string
     */
    protected function gerarNossoNumero()
    {
        $numero = self::zeroFill($this->getSequencial(), 8);
        return $numero;
    }

    protected function gerarDigitoVerificadorNossoNumero() {
        $sequencial = self::zeroFill($this->getSequencial(), 12);
        $digitoVerificador = static::modulo11($sequencial);

        return $digitoVerificador['digito'];
    }

    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \OpenBoleto\Exception
     */
     public function getCampoLivre()
     {
         return '9' . self::zeroFill($this->getConta(), 8) .
             self::zeroFill($this->getSequencial(), 12) .
             self::zeroFill($this->gerarDigitoVerificadorNossoNumero(), 1) .
             self::zeroFill($this->getIos(), 1) .
             self::zeroFill($this->getCarteira(), 1);
     }


    /**
     * Retorna o campo Agência/Cedente do boleto
     *
     * @return string
     */
    public function getAgenciaCodigoCedente()
    {
        $agencia = $this->getAgencia();
        $conta = $this->getUsoBanco();
        return $agencia . ' / ' . $conta;
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
        $part1  = $this->getCodigoBanco() . $this->getMoeda() . '9' . substr($this->getUsoBanco(), 0, 4);
        $part1 .= static::modulo10($part1);
        $part1  = substr($part1, 0, 5) . '.' . substr($part1, -5);

        $part2  = substr($this->getUsoBanco(), -3) . substr(self::zeroFill($this->getNossoNumero(), 13), 0, 7);
        $part2 .= static::modulo10($part2);
        $part2  = substr($part2, 0, 5) . '.' . substr($part2, -6);

        $part3  = substr(self::zeroFill($this->getNossoNumero(), 13), -6) . '0' . '101';
        $part3 .= static::modulo10($part3);
        $part3  = substr($part3, 0, 5) . '.' . substr($part3, -6);

        $part4 = $this->getDigitoVerificador();

        $part5  = $this->getFatorVencimento() . $this->getValorZeroFill();

        // Now put everything together.
        return "$part1 $part2 $part3 $part4 $part5";
    }

    public function getNumeroFebraban()
    {
        return self::zeroFill($this->getCodigoBanco(), 3) .
            $this->getMoeda() . $this->getDigitoVerificador() .
            $this->getFatorVencimento() .
            $this->getValorZeroFill() . '9' .
            $this->getUsoBanco() .
            self::zeroFill($this->getNossoNumero(), 13) .
            '0' . '101';
    }

    protected function getDigitoVerificador()
    {
        $base = "2345678923456789234567892345678923456789234";
        $num = self::zeroFill($this->getCodigoBanco(), 3) . $this->getMoeda() . $this->getFatorVencimento() . $this->getValorZeroFill() . '9' . $this->getUsoBanco() . self::zeroFill($this->getNossoNumero(), 13) . '0' . '101';
        $inv = '';
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $inv .= substr($num, $i, 1);
        }

        $soma = 0;
        for ($i = 0; $i < strlen($base); $i++) {
            $soma += (int) substr($inv, $i, 1) * (int) substr($base, $i, 1);
//            echo substr($inv, $i, 1) . ' x ' . substr($base, $i, 1) . ' = ' . $soma .PHP_EOL;
        }
        $soma = $soma * 10;
        $dv = $soma % 11;
        if ($dv == 0 || $dv == 10)
        {
            $dv = 1;
        }

        return $dv;
    }

    /**
     * Define variáveis da view específicas do boleto do Santander
     *
     * @return array
     */
    public function getViewVars()
    {
        return array(
            'esconde_uso_banco' => false
        );
    }
}


