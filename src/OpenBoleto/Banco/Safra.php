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
    // protected function gerarDacNossoNumero()
    // {
    //     $carteira = self::zeroFill($this->getCarteira(), 3);
    //     $sequencial = self::zeroFill($this->getSequencial(), 9);
    //     if (in_array($this->getCarteira(), array('2'))) {
    //         $this->dacNossoNumero = static::modulo10($carteira . $sequencial);
    //     } else {
    //         $agencia = self::zeroFill($this->getAgencia(), 5);
    //         $conta = self::zeroFill($this->getConta(), 6);
    //         $this->dacNossoNumero = static::modulo10($agencia . $conta . $carteira . $sequencial);
    //     }
    // }

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

        $sequencial = self::zeroFill($this->getSequencial(), 9);
        $carteira = self::zeroFill($this->getCarteira(), 1);
        $agencia = self::zeroFill($this->getAgencia(), 5);
        $conta = self::zeroFill($this->getConta(), 6);

        // Carteira 198 - (Nosso Número com 15 posições) - Anexo 5 do manual
        if (in_array($this->getCarteira(), array('107', '122', '142', '143', '196', '198'))) {
            $codigo = $carteira . $sequencial .
                self::zeroFill($this->getNumeroDocumento(), 7) .
                self::zeroFill($this->getCodigoCliente(), 5);

            // Define o DV da carteira para a view
            $this->carteiraDv = $modulo = static::modulo10($codigo);

            return $this->campoLivre = $codigo . $modulo . '0';
        }

        // Geração do DAC - Anexo 4 do manual
        if (!in_array($this->getCarteira(), array('126', '131', '146', '150', '168'))) {
            // Define o DV da carteira para a view
            $this->carteiraDv = $dvAgContaCarteira = static::modulo10($agencia . $conta . $carteira . $sequencial);
        } else {
            // Define o DV da carteira para a view
            $this->carteiraDv = $dvAgContaCarteira = static::modulo10($carteira . $sequencial);
        }

        // Módulo 10 Agência/Conta
        $dvAgConta = static::modulo10($agencia . $conta);

        return $this->campoLivre = $carteira . $sequencial . $dvAgContaCarteira . $agencia . $conta . $dvAgConta . '000';
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
}
