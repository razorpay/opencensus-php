import {
  API_NETWORK_CODES_MAP,
  CARD_NETWORK_MAP,
  IIN_CARD_NETWORK_MAP,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/card';
import { one_card } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/cardless_emi';
import { CARDLESS_EMI_SORT_ORDER } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/emi';
import * as METHODS from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/method-names';
import { cardNetwork } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/types';

import { getCDNUrl } from './getCDNUrl';
import { isBajajCard } from './methods';

export function getInstrumentLogo<T extends keyof typeof METHODS>(method: T, instrument: unknown) {
  if (!(typeof instrument === 'string')) {
    return '';
  }

  switch (method) {
    case METHODS.emi:
    case METHODS.cardless_emi: {
      const isPNG = instrument === one_card;
      // if cardless emi provider is the instrument name use cardless emi cdn
      // else use emi cdn
      if (CARDLESS_EMI_SORT_ORDER.includes(instrument) || isBajajCard(instrument as cardNetwork)) {
        return getCDNUrl(`cardless_emi-sq/${instrument.toLowerCase()}.${isPNG ? 'png' : 'svg'}`);
      }
      return getCDNUrl(`bank/${instrument}.gif`);
    }
    case METHODS.netbanking:
      return getCDNUrl(`bank/${instrument}.gif`);
    case METHODS.wallet:
      return getCDNUrl(`wallet-sq/${instrument}.png`);
    case METHODS.upi:
      return getCDNUrl(`app/${instrument}.svg`);
    case METHODS.card: {
      if (instrument === one_card) {
        return getCDNUrl(`cardless_emi-sq/${instrument}.png`);
      }
      const isCardNetworkPath = Object.keys(CARD_NETWORK_MAP).includes(instrument);
      if (
        Object.keys(API_NETWORK_CODES_MAP).includes(instrument) ||
        isCardNetworkPath ||
        Object.keys(IIN_CARD_NETWORK_MAP).includes(instrument)
      ) {
        const iconPath = isCardNetworkPath
          ? CARD_NETWORK_MAP[instrument as keyof typeof CARD_NETWORK_MAP]
          : API_NETWORK_CODES_MAP[instrument as keyof typeof API_NETWORK_CODES_MAP] ||
            IIN_CARD_NETWORK_MAP[instrument as keyof typeof IIN_CARD_NETWORK_MAP];
        return getCDNUrl(`card-networks/${iconPath || 'default'}.svg`);
      }

      // only bank logo supported
      return getCDNUrl(`bank/${instrument?.toUpperCase()}.gif`);
    }
    case METHODS.paylater:
      return getCDNUrl(`paylater-sq/${instrument}.svg`);
    case METHODS.international:
      return getCDNUrl(`international/${instrument}.png`);
    case METHODS.intl_bank_transfer:
      return getCDNUrl(`international/${instrument}`);
    case METHODS.app:
      return getCDNUrl(`app/${instrument}.png`);
    case METHODS.razorpay_club:
      return getCDNUrl(`app/${instrument}.svg`);
    default:
      return '';
  }
}
