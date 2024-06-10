import { INSTRUMENT_CODES_MAP } from 'merchant/views/EcosystemDowntimes/constants';
import {
  MULTIPLE_METHODS_DOWNTIME_MESSAGE,
  MULTIPLE_CARD_METHODS_DOWNTIME_MESSAGE,
  MULTIPLE_CARD_ISSUERS_DOWNTIME_MESSAGE,
  MULTIPLE_CARD_NETWORKS_DOWNTIME_MESSAGE,
  MULTIPLE_UPI_DOWNTIME_MESSAGE,
  NETBANKING_MULTIPLE_BANKS_DOWNTIME_MESSAGE,
  MULTIPLE_EMANDATE_BANKS_DOWNTIME_MESSAGE,
  MULTIPLE_UPI_VPA_HANDLE_MESSAGE,
  MULTIPLE_UPI_PSP_MESSAGE,
} from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics/DowntimeBanner/constants/downtime';

interface IBannerConfig {
  shouldShowBanner: boolean;
  bannerMessage: string;
}

const getInstrumentNames = (instrumentCodes): string[] =>
  instrumentCodes.map((code) => INSTRUMENT_CODES_MAP[code].name);

const formatBankNames = (banks): string => {
  if (banks.length === 1) return banks[0];
  return `${banks.slice(0, -1).join(', ')} and ${banks.slice(-1)}`;
};

const getCardsDowntimeBannerMessage = (cardDowntime: any): string => {
  if (Object.keys(cardDowntime).length > 1) {
    return MULTIPLE_CARD_METHODS_DOWNTIME_MESSAGE;
  }

  if (cardDowntime.issuer) {
    const issuersUnderDowntime = Object.keys(cardDowntime.issuer);
    const issuersDowntimeCount = issuersUnderDowntime.length;

    if (issuersDowntimeCount > 2) {
      // multiple issuers under downtime //
      return MULTIPLE_CARD_ISSUERS_DOWNTIME_MESSAGE;
    }
    const issuerNames = getInstrumentNames(issuersUnderDowntime);
    return `We are currently experiencing downtime on ${formatBankNames(
      issuerNames,
    )} that may impact your card payments. We are working with the bank(s) to resolve this at the earliest`;
  } else if (cardDowntime.network) {
    const networksUnderDowntime = Object.keys(cardDowntime.network);
    const networksUnderDowntimeCount = networksUnderDowntime.length;

    if (networksUnderDowntimeCount > 2) {
      // multiple networks under downtime //
      return MULTIPLE_CARD_NETWORKS_DOWNTIME_MESSAGE;
    }

    const networkNames = getInstrumentNames(networksUnderDowntime);
    return `We are currently experiencing downtime on ${formatBankNames(
      networkNames,
    )}. We are working with our partners to resolve this at the earliest.`;
  }

  return '';
};

const getUPIDowntimeBannerMessage = (upiDowntime: any): string => {
  if (Object.keys(upiDowntime).length > 1) {
    return MULTIPLE_UPI_DOWNTIME_MESSAGE;
  }

  if (upiDowntime.vpa_handle) {
    const vpasUnderDowntime = Object.keys(upiDowntime.vpa_handle);
    const vpasUnderDowntimeCount = vpasUnderDowntime.length;

    if (vpasUnderDowntimeCount > 2) {
      return MULTIPLE_UPI_VPA_HANDLE_MESSAGE;
    }

    const vpaNames = getInstrumentNames(vpasUnderDowntime);
    return `We are currently experiencing downtime on UPI VPA ${formatBankNames(
      vpaNames,
    )}. We are working with our partners to resolve this at the earliest`;
  } else if (upiDowntime.psp) {
    const pspUnderDowntime = Object.keys(upiDowntime.psp);
    const pspUnderDowntimeCount = pspUnderDowntime.length;

    if (pspUnderDowntimeCount > 2) {
      return MULTIPLE_UPI_PSP_MESSAGE;
    }

    const pspNames = getInstrumentNames(pspUnderDowntime);
    return `We are currently experiencing downtime on ${formatBankNames(
      pspNames,
    )}. We are working with our partners to resolve this at the earliest`;
  }

  return '';
};

const getNetbankingDowntimeBannerMessage = (netbankingDowntime: any): string => {
  if (netbankingDowntime.bank) {
    const banksUnderDowntime = Object.keys(netbankingDowntime.bank);
    const banksUnderDowntimeCount = banksUnderDowntime.length;

    if (banksUnderDowntimeCount > 2) {
      return NETBANKING_MULTIPLE_BANKS_DOWNTIME_MESSAGE;
    }

    const bankNames = getInstrumentNames(banksUnderDowntime);
    return `We are currently experiencing downtime on ${formatBankNames(
      bankNames,
    )}. We are working with the bank(s) to resolve this at the earliest`;
  }

  return '';
};

const getEmandateDowntimeBannerMessage = (emandateDowntime: any): string => {
  if (emandateDowntime.bank) {
    const banksUnderDowntime = Object.keys(emandateDowntime.bank);
    const banksUnderDowntimeCount = banksUnderDowntime.length;

    if (banksUnderDowntimeCount > 2) {
      return MULTIPLE_EMANDATE_BANKS_DOWNTIME_MESSAGE;
    }

    const bankNames = getInstrumentNames(banksUnderDowntime);
    return `We are currently experiencing downtime on ${formatBankNames(
      bankNames,
    )} emandate banks. We are working with the bank(s) to resolve this at the earliest`;
  }

  return '';
};

export const getDowntimeBannerConfig = ({
  activeDowntimes,
}: Record<string, any>): IBannerConfig => {
  const config = {
    shouldShowBanner: false,
    bannerMessage: '',
  };

  const downtimes = Object.keys(activeDowntimes);

  if (downtimes.length === 0) {
    return config;
  } else if (downtimes.length > 1) {
    return {
      ...config,
      shouldShowBanner: true,
      bannerMessage: MULTIPLE_METHODS_DOWNTIME_MESSAGE,
    };
  }

  if (activeDowntimes?.card) {
    config.shouldShowBanner = true;
    config.bannerMessage = getCardsDowntimeBannerMessage(activeDowntimes.card);
  } else if (activeDowntimes?.upi) {
    config.shouldShowBanner = true;
    config.bannerMessage = getUPIDowntimeBannerMessage(activeDowntimes.upi);
  } else if (activeDowntimes?.netbanking) {
    config.shouldShowBanner = true;
    config.bannerMessage = getNetbankingDowntimeBannerMessage(activeDowntimes.netbanking);
  } else if (activeDowntimes?.emandate) {
    config.shouldShowBanner = true;
    config.bannerMessage = getEmandateDowntimeBannerMessage(activeDowntimes.emandate);
  }

  return config;
};
