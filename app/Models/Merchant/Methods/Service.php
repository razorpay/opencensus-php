<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card\Network;
use RZP\Models\Card\Type;
use RZP\Models\Emi\DebitProvider;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Merchant;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function isMethodEnabledForMerchant(string $method, Merchant\Entity $merchant)
    {
        $methods = $merchant->getMethods();

        return $methods->isMethodEnabled($method);
    }

    public function internalGetPaymentInstruments()
    {
        //cards
        $cardNetworks = array_keys(Network::getSupportedNetworksNamesMap());
        $cardTypes = Type::getCardTypes();

        //netbanking
        $netbankingBanks = Netbanking::getSupportedBanks();
        $netbankingBankNamesMap = array_keys(Netbanking::getNames($netbankingBanks));

        //emi
        $debitEmiProviders = DebitProvider::getAllDebitEmiProviders();

        //paylater
        $paylaterProviders = Paylater::getPaylaterDirectAquirers();

        //wallet
        $walletNetworkNames = array_keys(Wallet::getWalletNetworkNamesMap());

        //cardlessEmi
        $cardlessEmiProviders = CardlessEmi::getCardlessEmiDirectAquirers();

        //cards
        $cards = [];
        foreach ($cardNetworks as $cardNetwork)
        {
            foreach ($cardTypes as $cardType)
            {
                array_push($cards, [
                    'network' => $cardNetwork,
                    'type'  => $cardType,
                ]);
            }
        }

        //netbanking
        $netbanking = [];
        foreach ($netbankingBankNamesMap as $netbankingBank)
        {
            array_push($netbanking, [
                'bank' => $netbankingBank,
            ]);
        }

        //emi
        $emi = [];
        foreach ($debitEmiProviders as $debitEmiProvider)
        {
            array_push($emi, [
                'type' => 'debit',
                'provider' => $debitEmiProvider,
            ]);
        }

        //hardcoding credit emi for now as we dont have any credit emi providers as of now
        array_push($emi, [
            'type' => 'credit',
            'provider' => null,
        ]);

        //paylater
        $paylater = [];
        foreach ($paylaterProviders as $paylaterProvider)
        {
            array_push($paylater, [
                'provider' => $paylaterProvider,
            ]);
        }

        //wallet
        $wallets = [];
        foreach ($walletNetworkNames as $walletNetworkName)
        {
            array_push($wallets, [
                'provider' => $walletNetworkName
            ]);
        }

        //cardlessEmi
        $cardlessEmi = [];
        foreach ($cardlessEmiProviders as $cardlessEmiProvider)
        {
            array_push($cardlessEmi, [
                'provider' => $cardlessEmiProvider,
            ]);
        }

        $data = [
            'card' => $cards,
            'netbanking' => $netbanking,
            'emi' => $emi,
            'paylater' => $paylater,
            'wallets' => $wallets,
            'cardless_emi' => $cardlessEmi,
        ];

        $this->trace->info(TraceCode::MERCHANT_PAYMENT_INSTRUMENTS_FETCH_RESPONSE, [
            'data' => $data,
        ]);

        return $data;
    }
}
