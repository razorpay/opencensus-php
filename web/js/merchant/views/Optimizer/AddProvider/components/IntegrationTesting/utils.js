import { Badge, CheckIcon, CloseIcon } from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { METHODS_MAP } from 'merchant/views/Navigator/constants';
import { BANKS_LIST } from 'merchant/views/Optimizer/AddProvider/bankList';

import { CARD_TYPES, CARD_NETWORKS, WALLETS_MAP, GATEWAY_NAMES, BANK_TYPES } from './constants';

// return enabled methods for a given coverage data
const getEnabledMethodsFromCoverage = (coverage) => {
  const enabledMethods = [];
  coverage.forEach((item) => {
    if (item?.enabled) {
      enabledMethods.push(item.method);
    }
  });
  return enabledMethods;
};

export const getInstrumentCoverageTabsList = (razorpayCoverage, gatewayCoverage) => {
  const razorpayMethods = getEnabledMethodsFromCoverage(razorpayCoverage);
  const gatewayMethods = getEnabledMethodsFromCoverage(gatewayCoverage);

  // get all unique enabled methods
  const tabsMethods = [];
  razorpayMethods.forEach((item) => {
    tabsMethods.push(item);
  });
  gatewayMethods.forEach((item) => {
    if (!tabsMethods.includes(item)) {
      tabsMethods.push(item);
    }
  });

  const tabsList = [];
  const notCoveredForCoverage = [];
  if (tabsMethods.includes('card')) {
    tabsList.push({
      label: 'Cards',
      value: 'card',
    });
  } else {
    notCoveredForCoverage.push('card');
  }
  if (tabsMethods.includes('upi')) {
    tabsList.push({
      label: 'UPI',
      value: 'upi',
    });
  } else {
    notCoveredForCoverage.push('upi');
  }
  if (tabsMethods.includes('netbanking')) {
    tabsList.push({
      label: 'Netbanking',
      value: 'netbanking',
    });
  } else {
    notCoveredForCoverage.push('netbanking');
  }
  if (tabsMethods.includes('wallet')) {
    tabsList.push({
      label: 'Wallets',
      value: 'wallet',
    });
  } else {
    notCoveredForCoverage.push('wallet');
  }
  const otherMethods = [];
  razorpayCoverage.forEach(
    (item) =>
      !['card', 'upi', 'netbanking', 'wallet'].includes(item.method) &&
      otherMethods.push(item.method),
  );
  otherMethods.push(...notCoveredForCoverage);

  if (otherMethods.length > 0) {
    tabsList.push({
      label: 'Others',
      value: 'others',
    });
  }

  return {
    tabsList,
    otherMethods,
  };
};

const CoverageBadge = ({ status }) => {
  return (
    <Badge color={status} icon={status === 'positive' ? CheckIcon : CloseIcon}>
      {status === 'positive' ? 'Covered' : 'Not covered'}
    </Badge>
  );
};

export const getCardCoverageData = (gatewayCoverage, razorpayCoverage) => {
  const cardGatewayCoverageData = gatewayCoverage.filter((item) => item.method === 'card');
  const cardGatewayCoverage = cardGatewayCoverageData[0]?.card;

  const cardRazorpayCoverageData = razorpayCoverage.filter((item) => item.method === 'card');
  const cardRazorpayCoverage = cardRazorpayCoverageData[0]?.card;

  const data = [];
  Object.keys(cardRazorpayCoverage).forEach((key) => {
    cardRazorpayCoverage[key]?.network?.forEach((networkItem) => {
      data.push({
        cardType: key,
        cardNetwork: networkItem,
        gatewayCoverage: !!cardGatewayCoverage?.[key]?.network?.includes(networkItem),
        razorpayCoverage: true,
      });
    });
  });

  return data;
};

export const getCardCoverageColumns = (gateway) => {
  return [
    {
      label: 'Card Type',
      value: (item) => CARD_TYPES[item.cardType] || item.cardType,
    },
    {
      label: 'Card Network',
      value: (item) => CARD_NETWORKS[item.cardNetwork] || item.cardNetwork,
    },
    {
      label: 'On Razorpay',
      value: (item) => <CoverageBadge status={item.razorpayCoverage ? 'positive' : 'negative'} />,
    },
    {
      label: `On ${GATEWAY_NAMES[gateway]}`,
      value: (item) => <CoverageBadge status={item.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};

export const getUPICoverageData = (gatewayCoverage, razorpayCoverage) => {
  const upiGatewayCoverageData = gatewayCoverage.filter((item) => item.method === 'upi');
  const upiGatewayCoverage = upiGatewayCoverageData[0]?.upi;

  const upiRazorpayCoverageData = razorpayCoverage.filter((item) => item.method === 'upi');
  const upiRazorpayCoverage = upiRazorpayCoverageData[0]?.upi;

  const data = [];
  Object.keys(upiRazorpayCoverage).forEach((key) => {
    data.push({
      type: key,
      gatewayCoverage: !!upiGatewayCoverage?.[key],
      razorpayCoverage: !!upiRazorpayCoverage?.[key],
    });
  });

  return data;
};

export const getUPICoverageColumns = (gateway) => {
  return [
    {
      label: 'UPI flow',
      value: (item) => titleCase(item.type),
    },
    {
      label: 'On Razorpay',
      value: (item) => <CoverageBadge status={item?.razorpayCoverage ? 'positive' : 'negative'} />,
    },
    {
      label: `On ${GATEWAY_NAMES[gateway]}`,
      value: (item) => <CoverageBadge status={item?.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};

export const getNetbankingCoverageData = (gatewayCoverage, razorpayCoverage) => {
  const netbankingGatewayCoverageData = gatewayCoverage.filter(
    (item) => item.method === 'netbanking',
  );
  const netbankingGatewayCoverage = netbankingGatewayCoverageData[0]?.netbanking;

  const netbankingRazorpayCoverageData = razorpayCoverage.filter(
    (item) => item.method === 'netbanking',
  );
  const netbankingRazorpayCoverage = netbankingRazorpayCoverageData[0]?.netbanking;

  const data = [];
  netbankingRazorpayCoverage?.banks?.forEach((bank) => {
    // Split bank code and retail/corporate
    let bankCode = bank;
    let retailOrCorporate;
    if (bank.split('_').length > 1) {
      bankCode = bank.split('_')[0];
      retailOrCorporate = bank.split('_')[1];
    }

    // Bank name based on bank code and retail/corporate
    let bankName = bank;
    if (BANKS_LIST[bankCode]) {
      if (retailOrCorporate && BANK_TYPES[retailOrCorporate]) {
        bankName = `${BANKS_LIST[bankCode]} - ${BANK_TYPES[retailOrCorporate]}`;
      } else {
        bankName = BANKS_LIST[bankCode];
      }
    }
    data.push({
      bank: bankName,
      gatewayCoverage: !!netbankingGatewayCoverage?.banks?.includes(bank),
      razorpayCoverage: true,
    });
  });

  return data;
};

export const getNetbankingCoverageColumns = (gateway) => {
  return [
    {
      label: 'Bank',
      value: (item) => item.bank,
    },
    {
      label: 'On Razorpay',
      value: (item) => <CoverageBadge status={item?.razorpayCoverage ? 'positive' : 'negative'} />,
    },
    {
      label: `On ${GATEWAY_NAMES[gateway]}`,
      value: (item) => <CoverageBadge status={item?.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};

export const getWalletCoverageData = (gatewayCoverage, razorpayCoverage) => {
  const walletGatewayCoverageData = gatewayCoverage.filter((item) => item.method === 'wallet');
  const walletGatewayCoverage = walletGatewayCoverageData[0]?.wallets;

  const walletRazorpayCoverageData = razorpayCoverage.filter((item) => item.method === 'wallet');
  const walletRazorpayCoverage = walletRazorpayCoverageData[0]?.wallets;

  const data = [];
  Object.keys(walletRazorpayCoverage).forEach((key) => {
    data.push({
      issuer: WALLETS_MAP[key] || key,
      gatewayCoverage: !!walletGatewayCoverage?.[key],
      razorpayCoverage: !!walletRazorpayCoverage?.[key],
    });
  });

  return data;
};

export const getWalletCoverageColumns = (gateway) => {
  return [
    {
      label: 'Wallet Issuer',
      value: (item) => item.issuer,
    },
    {
      label: 'On Razorpay',
      value: (item) => <CoverageBadge status={item?.razorpayCoverage ? 'positive' : 'negative'} />,
    },
    {
      label: `On ${GATEWAY_NAMES[gateway]}`,
      value: (item) => <CoverageBadge status={item?.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};

export const getOtherMethodsCoverageData = (gatewayCoverage, razorpayCoverage, otherMethods) => {
  const data = [];
  otherMethods.forEach((method) => {
    data.push({
      method,
      gatewayCoverage: !!gatewayCoverage.find((gatewayItem) => gatewayItem.method === method)
        ?.enabled,
      razorpayCoverage: !!razorpayCoverage.find((gatewayItem) => gatewayItem.method === method)
        ?.enabled,
    });
  });

  return data;
};

export const getOtherMethodsCoverageColumns = (gateway) => {
  return [
    {
      label: 'Method',
      value: (item) => METHODS_MAP[item.method] || item.method,
    },
    {
      label: 'On Razorpay',
      value: (item) => <CoverageBadge status={item?.razorpayCoverage ? 'positive' : 'negative'} />,
    },
    {
      label: `On ${GATEWAY_NAMES[gateway]}`,
      value: (item) => <CoverageBadge status={item?.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};
