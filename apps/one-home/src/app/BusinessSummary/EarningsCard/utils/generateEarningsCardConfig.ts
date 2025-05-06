import { PaymentGatewayIcon, PosIcon } from '@razorpay/blade/components';
import { isDefined } from '@libs/shared-utils';
import { paiseToRupees } from '@libs/shared-utils';
import { EarningsData, EarningsItem } from '../types';

const generateEarningsCardConfig = (dataObj: any): EarningsData | null => {
  if (!dataObj) return null;

  const { data_summary, payment_enabled, payment_locked, offline_payment_enabled } = dataObj;

  // Handle Growth State (Disabled Payments)
  if (payment_enabled === false) {
    return {
      type: 'growth',
      lastUpdated: data_summary?.last_updated,
      inputTime: data_summary?.input_time,
    };
  }

  // Handle Locked State
  if (payment_locked) {
    return {
      type: 'locked',
      lastUpdated: data_summary?.last_updated,
      inputTime: data_summary?.input_time,
    };
  }

  // Define Earnings Categories & Actions
  const earningsTypes = [
    {
      key: 'payments',
      title: 'Online',
      icon: PaymentGatewayIcon,
      amountKeys: [
        {
          key: 'online_domestic_amount',
          title: 'Domestic (India) Payments',
          enableKey: 'payment_enabled',
        },
        {
          key: 'online_international_amount',
          title: 'International',
          enableKey: 'international_payment_enabled',
        },
      ],
      enableKey: 'payment_enabled',
      action: {
        type: 'navigate-internal',
        path: '/payments',
        label: 'Online Payments',
      },
    },
    {
      key: 'offline',
      title: offline_payment_enabled ? 'Offline | Razorpay POS' : 'Offline payments with POS',
      icon: PosIcon,
      amountKeys: [
        {
          key: 'offline_amount',
          title: '',
          enableKey: 'offline_payment_enabled',
        },
      ],
      enableKey: 'offline_payment_enabled',
      action: {
        type: 'navigate-internal',
        path: '/pos/catalog',
        label: 'Offline payments with POS',
      },
    },
  ];

  // Process Earnings Items
  const earnings: EarningsItem[] = earningsTypes.map(
    ({ key, title, icon, amountKeys, enableKey, action }) => {
      // Filter out amountKeys based on their specific 'enabled' property
      const filteredAmountKeys = amountKeys.filter(({ enableKey }) => dataObj[enableKey]);

      const totalAmount = filteredAmountKeys.reduce(
        (sum, { key }) => sum + Number(dataObj[key] || 0),
        0,
      );

      const isEnabled = dataObj[enableKey];

      return {
        title,
        amount: paiseToRupees(totalAmount), // Show earnings only if enabled
        currency: 'INR',
        icon,
        showComponent: isEnabled, // If false, show button
        action: isEnabled ? null : action,
        breakup: filteredAmountKeys
          .map(({ key, title, enableKey }) => ({
            title,
            amount: paiseToRupees(dataObj[key] || 0),
            currency: 'INR',
            showComponent: isDefined(dataObj[enableKey]), // safe check
          }))
          .filter((item) => item.showComponent),
      };
    },
  );

  return {
    type: 'earnings',
    total: paiseToRupees(Number(data_summary?.current_data ?? 0)),
    currency: 'INR',
    percentageChange: Number(data_summary?.percentage_change ?? 0),
    lastUpdated: Number(data_summary?.last_updated),
    inputTime: data_summary?.input_time,
    earnings,
  };
};

export default generateEarningsCardConfig;
