import { BulkPayoutsIcon, SourceToPayIcon, RazorpayxPayrollIcon } from '@razorpay/blade/components';
import { SpendingsData, SpendingsItem } from '../types';
import { paiseToRupees } from '@libs/shared-utils';
import getIn from './getIn';

const generateSpendingsCardConfig = (dataObj: any): SpendingsData | null => {
  if (!dataObj) return null;

  const {
    data_summary,
    business_banking_enabled,
    locked,
    payroll,
    vendor_payouts_enabled,
    api_and_bulk_payouts_enabled,
  } = dataObj;

  // Handle Growth State (Disabled Banking)
  if (!business_banking_enabled) {
    return {
      type: 'growth',
      lastUpdated: Number(data_summary?.last_updated),
      inputTime: data_summary?.input_time,
    };
  }

  // Handle Locked State
  if (locked) {
    return {
      type: 'locked',
      lastUpdated: Number(data_summary?.last_updated),
      inputTime: data_summary?.input_time,
    };
  }

  // Define Spendings Categories & Actions
  const spendingsTypes = [
    {
      key: 'api_and_bulk_payouts',
      title: 'API and Bulk Payouts',
      icon: BulkPayoutsIcon,
      amountKeys: [
        {
          key: 'api_and_bulk_payouts_amount',
          title: 'API and Bulk Payouts',
          enableKey: 'api_and_bulk_payouts_enabled',
        },
      ],
      enableKey: 'api_and_bulk_payouts_enabled',
      action: {
        type: 'navigate-internal',
        path: '/banking/payouts/bulk',
        url: 'https://x.razorpay.com/payouts/bulk?utm_source=r1_dashboard&utm_content=home_business_summary',
        label: 'View API & Bulk Payouts',
      },
    },
    {
      key: 'vendor_payouts',
      title: vendor_payouts_enabled ? 'S2P | Vendor Payouts' : 'Automate account payables with S2P',
      icon: SourceToPayIcon,
      amountKeys: [
        {
          key: 'vendor_payouts_amount',
          title: 'S2P | Vendor Payouts',
          enableKey: 'vendor_payouts_enabled',
        },
      ],
      enableKey: 'vendor_payouts_enabled',
      action: {
        type: 'navigate-internal',
        path: '/banking/vendor-payouts',
        url: 'https://x.razorpay.com/vendor-payouts?utm_source=r1_dashboard&utm_content=home_business_summary',
        label: 'Automate account payables with S2P',
      },
    },
    {
      key: 'payroll',
      title: payroll?.enabled ? 'Payroll' : 'Pay salaries with Razorpay Payroll',
      icon: RazorpayxPayrollIcon,
      amountKeys: [{ key: 'payroll_amount', title: 'Payroll', enableKey: 'payroll.enabled' }],
      enableKey: 'payroll.enabled',
      action: {
        type: 'navigate-external',
        path: '/payroll',
        url: 'https://payroll.razorpay.com/login?utm_source=r1_dashboard&utm_content=home_business_summary',
        label: 'Pay salaries with Razorpay Payroll',
      },
    },
  ];

  //  Process Spendings Items
  const spendings: SpendingsItem[] = spendingsTypes.map(
    ({ key, title, icon, amountKeys, enableKey, action }) => {
      const filteredAmountKeys = amountKeys.filter(({ enableKey }) =>
        Boolean(getIn(dataObj, enableKey)),
      );

      const totalAmount = filteredAmountKeys.reduce(
        (sum, { key }) => sum + Number(dataObj[key] || 0),
        0,
      );

      const isEnabled = getIn(dataObj, enableKey);

      return {
        title,
        amount: paiseToRupees(totalAmount),
        currency: 'INR',
        icon,
        showComponent: isEnabled,
        action: isEnabled ? undefined : action,
        breakup: filteredAmountKeys
          .map(({ key, title, enableKey }) => ({
            title,
            amount: paiseToRupees(dataObj[key] || 0),
            currency: 'INR',
            showComponent: Boolean(getIn(dataObj, enableKey)),
          }))
          .filter((item) => item.showComponent),
      };
    },
  );

  return {
    type: 'spendings',
    total: paiseToRupees(Number(data_summary?.current_data ?? 0)),
    currency: 'INR',
    percentageChange: Number(data_summary?.percentage_change ?? 0),
    lastUpdated: Number(data_summary?.last_updated),
    inputTime: data_summary?.input_time,
    spendings,
  };
};

export default generateSpendingsCardConfig;
