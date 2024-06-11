import { Badge, CheckIcon, CloseIcon } from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { METHODS_MAP } from 'merchant/views/Navigator/constants';
import { BANKS_LIST } from 'merchant/views/Optimizer/AddProvider/bankList';

import { CARD_TYPES, CARD_NETWORKS, WALLETS_MAP } from './constants';

// To-do: Implement getTabsList
export const getInstrumentCoverageTabsList = () => {
  return [
    {
      label: 'Cards',
      value: 'card',
    },
    {
      label: 'UPI',
      value: 'upi',
    },
    {
      label: 'Netbanking',
      value: 'netbanking',
    },
    {
      label: 'Wallets',
      value: 'wallet',
    },
    {
      label: 'Others',
      value: 'others',
    },
  ];
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
  Object.keys(cardGatewayCoverage).forEach((key) => {
    cardGatewayCoverage[key]?.network?.forEach((networkItem) => {
      data.push({
        cardType: key,
        cardNetwork: networkItem,
        gatewayCoverage: true,
        razorpayCoverage: !!cardRazorpayCoverage?.[key]?.network?.includes(networkItem),
      });
    });
  });

  return data;
};

export const getCardCoverageColumns = (gateway) => {
  return [
    {
      label: 'Card Type',
      value: (item) => CARD_TYPES[item.cardType],
    },
    {
      label: 'Card Network',
      value: (item) => CARD_NETWORKS[item.cardNetwork],
    },
    {
      label: 'On Razorpay',
      value: (item) => <CoverageBadge status={item.razorpayCoverage ? 'positive' : 'negative'} />,
    },
    {
      label: `On ${gateway}`,
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
  Object.keys(upiGatewayCoverage).forEach((key) => {
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
      label: `On ${gateway}`,
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
  netbankingGatewayCoverage?.banks?.forEach((bank) => {
    data.push({
      bank: BANKS_LIST[bank] || bank,
      gatewayCoverage: true,
      razorpayCoverage: !!netbankingRazorpayCoverage?.banks?.includes(bank),
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
      label: `On ${gateway}`,
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
  Object.keys(walletGatewayCoverage).forEach((key) => {
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
      label: `On ${gateway}`,
      value: (item) => <CoverageBadge status={item?.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};

export const getOtherMethodsCoverageData = (gatewayCoverage, razorpayCoverage) => {
  const otherMethodsGatewayCoverageData = gatewayCoverage.filter(
    (item) => !['card', 'upi', 'netbanking', 'wallet'].includes(item.method),
  );

  const data = [];
  otherMethodsGatewayCoverageData.forEach((item) => {
    data.push({
      method: item.method,
      gatewayCoverage: !!item?.enabled,
      razorpayCoverage: !!razorpayCoverage.find(
        (razorpayItem) => razorpayItem.method === item.method,
      )?.enabled,
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
      label: `On ${gateway}`,
      value: (item) => <CoverageBadge status={item?.gatewayCoverage ? 'positive' : 'negative'} />,
    },
  ];
};
