import { MerchantCheckoutPaymentMethodDetails } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import {
  PAYMENT_NETWORK,
  PAYMENT_NETWORK_CODE,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/card';
import { cardlessEmiConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/cardless_emi';
import {
  DEBIT_EMI_ISSUERS,
  emiBanks,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/emi';
import {
  paylaterConfig,
  paylaterOrder,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/paylater';
import { UPI_APPS } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/upi';
import { walletName } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/wallet';
import { cardNetwork } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/types';
import { STANDARD_PAYMENT_BLOCK_NAMES } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import { get as getValue } from './common';

export const getCod = (details: MerchantCheckoutPaymentMethodDetails) => {
  return {
    title: STANDARD_PAYMENT_BLOCK_NAMES.COD,
    description: 'Configure Cash on Delivery payment option',
    isEnabled: getValue(details, 'enabled.cod', false),
  };
};

export const getCard = (details: MerchantCheckoutPaymentMethodDetails) => {
  const networks = getValue(details, 'card.card_networks', {});
  const enabledNetworkds = Object.keys(PAYMENT_NETWORK)
    .filter((key) => networks[key])
    .map((networkCode) => {
      return {
        code: networkCode,
        name: PAYMENT_NETWORK[networkCode],
      };
    });
  return {
    title: 'Cards',
    name: STANDARD_PAYMENT_BLOCK_NAMES.CARDS,
    description: 'Configure card payment options',
    isEnabled: getValue(details, 'card.enabled', false),
    types: {
      credit: getValue(details, 'card.card_type.credit', false),
      debit: getValue(details, 'card.card_type.debit', false),
    },
    networks: enabledNetworkds,
  };
};

export const getEmi = (details: MerchantCheckoutPaymentMethodDetails) => {
  const emiOptions = getValue(details, 'emi.emi_options', {});
  const emiBanksMap = emiBanks.reduce((banks, bankObj) => {
    banks[bankObj.code] = bankObj;
    return banks;
  }, {});
  const creditEmiProviders = Object.keys(emiOptions)
    .map((provider) => {
      return {
        code: provider,
        name: emiBanksMap[provider]?.name,
      };
    })
    .filter(({ code }) => !DEBIT_EMI_ISSUERS.includes(code));

  const debitEmiProvidersMap = getValue(details, 'emi.debit_emi_providers', {});
  const availableDebitEMIProviders = Object.keys(debitEmiProvidersMap)
    .filter(Boolean)
    .map((provider) => {
      return {
        code: `${provider}_DC`,
        name: emiBanksMap[`${provider}_DC`]?.name,
      };
    });

  const availableCardlessEmiMap = getValue(details, 'addon_methods.affordability.cardless_emi', {});

  // If Bajaj EMI exisits it should be shown in cardless emi section as well
  if (emiOptions.BAJAJ) {
    availableCardlessEmiMap.bajaj = true;
  }

  const cardlessEmiProviders = Object.keys(cardlessEmiConfig)
    .map((key) => {
      return {
        code: key,
        details: cardlessEmiConfig[key],
      };
    })
    .filter(({ code }) => availableCardlessEmiMap[code]);

  return {
    title: 'EMI',
    name: STANDARD_PAYMENT_BLOCK_NAMES.EMI,
    description: 'Configure EMI and cardless EMI options',
    isEnabled: getValue(details, 'emi.enabled', false),
    providers: {
      debit: availableDebitEMIProviders,
      credit: creditEmiProviders,
      cardless: cardlessEmiProviders,
    },
  };
};

export const getNetbanking = (details: MerchantCheckoutPaymentMethodDetails) => {
  const banksMap = getValue(details, 'netbanking', {});
  const banks =
    Object.keys(banksMap).map((key) => ({
      code: key,
      name: banksMap[key],
    })) ?? [];
  return {
    title: 'Netbanking',
    name: STANDARD_PAYMENT_BLOCK_NAMES.NETBANKING,
    description: 'Configure various banks for netbanking',
    isEnabled: true,
    banks,
  };
};

export const getUpi = (details: MerchantCheckoutPaymentMethodDetails) => {
  const isUPIEnabled = getValue(details, 'upi.enabled', false);
  const upiType = getValue(details, 'upi.upi_type') || {
    collect: Number(isUPIEnabled),
    intent: Number(isUPIEnabled),
  };
  const enabledUPIApps = isUPIEnabled && upiType.intent ? UPI_APPS : [];
  return {
    title: 'UPI',
    name: STANDARD_PAYMENT_BLOCK_NAMES.UPI,
    description: 'Configure QR, Collect and Apps for UPI',
    isEnabled: isUPIEnabled,
    types: upiType,
    apps: enabledUPIApps,
  };
};

export const getPaylater = (details: MerchantCheckoutPaymentMethodDetails) => {
  const paylaterMap = getValue(details, 'addon_methods.affordability.paylater', {});
  const activePaylaterProviders = paylaterOrder
    .filter((key) => !!paylaterMap[key])
    .map((key) => {
      return {
        code: key,
        name: paylaterConfig[key].display_name ?? '',
      };
    });
  return {
    title: 'PayLater',
    name: STANDARD_PAYMENT_BLOCK_NAMES.PAYLATER,
    description: 'Configure various providers for paylater',
    providers: activePaylaterProviders,
    isEnabled: activePaylaterProviders.length > 0,
  };
};

export const getWallet = (details: MerchantCheckoutPaymentMethodDetails) => {
  const walletsMap = getValue(details, 'wallet', {});
  const wallets = Object.keys(walletName)
    .filter((key) => walletsMap[key])
    .map((key) => ({
      code: key,
      name: walletName[key],
    }));

  return {
    title: 'Wallets',
    name: STANDARD_PAYMENT_BLOCK_NAMES.WALLET,
    description: 'Configure various providers of wallets',
    isEnabled: wallets.length > 0,
    wallets,
  };
};

export const isBajajCard = (network: cardNetwork) => {
  return network === PAYMENT_NETWORK_CODE.BAJAJ;
};

export const allMethodGetters = [
  getCard,
  getEmi,
  getNetbanking,
  getUpi,
  getPaylater,
  getWallet,
  getCod,
];
