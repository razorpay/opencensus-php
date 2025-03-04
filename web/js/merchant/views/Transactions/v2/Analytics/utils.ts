import moment from 'moment';

import { Option, Options } from 'common/components/Dropdown/types';
import { Environments, Store } from 'common/typings';
import { titleCase } from 'common/utils/rzp-utils';
import {
  AnalyticsBoilerPlateData,
  BottomOverviewCardData,
  BottomOverviewCardsData,
  FailedOverviewResult,
  PaymentSplitMethodData,
  PaymentTypes,
  RefundResponse,
  SplitPaymentMethod,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { paymentMethodOptionsMap } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import {
  TransactionsEntityRoute,
  durationOptionsMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { Duration, DurationOption } from 'merchant/views/Transactions/v2/common/types';
import { generateOptions, getFromTime } from 'merchant/views/Transactions/v2/common/utils';

export const paymentDurationOptionsMap = {
  today: durationOptionsMap.today,
  last7Days: durationOptionsMap.last7Days,
  last30Days: durationOptionsMap.last30Days,
  last90Days: durationOptionsMap.last90Days,
};
export const durationSectionOptions = generateOptions(paymentDurationOptionsMap);
export const durationSectionName = 'Duration';
export const durationOptions = [
  {
    section: {
      name: durationSectionName,
      options: durationSectionOptions,
    },
  },
];

export const getOptions = (
  isMobile: boolean,
): {
  durationOptions: Options;
  defaultDuration: Option;
  defaultDate: Duration;
} => {
  const overviewDuration = sessionStorage.getItem('overviewDuration');
  const preSelectedDurationOption = overviewDuration && JSON.parse(overviewDuration);
  const endOfDay = moment().endOf('day');
  const defaultDuration = preSelectedDurationOption || durationSectionOptions[0];
  const defaultDate = {
    from: getFromTime(defaultDuration.value as DurationOption['value']).unix(),
    to: endOfDay.unix(),
  };
  if (isMobile) {
    return {
      defaultDuration,
      durationOptions: durationSectionOptions,
      defaultDate,
    };
  }
  return {
    defaultDuration,
    durationOptions,
    defaultDate,
  };
};

export const cardLink = {
  [PaymentTypes.Refunds]: TransactionsEntityRoute.REFUNDS,
  [PaymentTypes.Disputes]: TransactionsEntityRoute.DISPUTES,
  [PaymentTypes.Failed]: TransactionsEntityRoute.FAILED_PAYMENTS,
};

export const doughnutChartColors = ['#9381FF', '#C59B76', '#70C1B3', '#BBC7CF'];

export const isSrEnabledForUser = ({
  mode,
  user,
}: {
  mode: Environments;
  user: Store['session']['user'];
}): boolean => {
  return !!(
    mode === 'live' &&
    user.findTag('success_rate') &&
    user.isAllowedView &&
    user.isAllowedView('success_rate')
  );
};

export const getLandingPageAnalyticsToolTip = () => ({
  Collected:
    'Amount collected in your Dashboard account. It will be deposited in your bank account after deductions and adjustments as per your settlement cycle.',
  [PaymentTypes.Refunds]: "Amount reversed to the customer's bank account.",
  [PaymentTypes.Disputes]:
    'Amount adjusted for customer disputes, such as unauthorised charges or undelivered merchandise.',
  [PaymentTypes.Failed]:
    'Payments failed due to customer bank issues, customer drop-offs or technical errors on your end.',
});

export const getEntityPageAnalyticsToolTip = () => ({
  Refunds: {
    refunded:
      "We have processed the refund. The bank may take 5-7 working days to credit the amount to the customer's account.",
    processing: 'We are processing the refund. It may take up to 3-5 working days.',
    failed: 'Refunds failed due to customer account errors or bank-related issues.',
  },
  Failed: {
    failed:
      'Payments failed due to customer bank issues, customer drop-offs or technical errors on your end.',
    customerDroppOff:
      'Payments failed due to incorrect card details/OTP, insufficient balance or payment cancellation by the customer.',
    bankingFailures: "Payments failed due to technical or system issues at the customer's bank.",
    businessFailures:
      'Payments failed due to technical issues on your end, such as inactive payment methods or international payments.',
  },
});

export const getAnalyticsPropsForRefunds = (
  refundsData: RefundResponse,
): AnalyticsBoilerPlateData => {
  const { failed, processing, refunded } = refundsData;
  return {
    lead: {
      title: 'Refunded',
      value: refunded.amount,
      isAmount: true,
      subtitle: `from ${refunded.count} processed refunds`,
      toolTipText: getEntityPageAnalyticsToolTip().Refunds.refunded,
    },
    trail: [
      {
        title: 'Processing',
        value: processing.amount,
        isAmount: true,
        subtitle: `from ${processing.count} refunds`,
        toolTipText: getEntityPageAnalyticsToolTip().Refunds.processing,
      },
      {
        title: 'Failed',
        value: failed.amount,
        isAmount: true,
        subtitle: `from ${failed.count} refunds`,
        toolTipText: getEntityPageAnalyticsToolTip().Refunds.failed,
      },
    ],
  };
};

export const getAnalyticsPropsForFailedPyaments = (
  failedPaymentsData: number,
  failureInfo: FailedOverviewResult,
): AnalyticsBoilerPlateData => {
  return {
    lead: {
      title: 'Failed',
      value: failedPaymentsData,
      subtitle: 'payments',
      toolTipText: getEntityPageAnalyticsToolTip().Failed.failed,
    },
    trail: [
      {
        title: 'Bank-Related',
        subtitle: 'payments',
        value: failureInfo.bank.value,
        toolTipText: getEntityPageAnalyticsToolTip().Failed.bankingFailures,
      },
      {
        title: 'Customer drop-offs',
        value: failureInfo.customer.value,
        subtitle: 'payments',
        toolTipText: getEntityPageAnalyticsToolTip().Failed.customerDroppOff,
      },
      {
        title: 'Business failures or others',
        value: failureInfo.others.value + failureInfo.business.value,
        subtitle: 'payments',
        toolTipText: getEntityPageAnalyticsToolTip().Failed.businessFailures,
      },
    ],
  };
};

export const getBottomSectionData = ({
  data,
}: BottomOverviewCardsData): BottomOverviewCardData[] => {
  const { disputes, failed, refund } = data;
  return [
    {
      name: PaymentTypes.Refunds,
      value: refund.amount,
      loading: refund.loading,
      failed: refund.failed,
      visible: true,
      isAmount: true,
    },
    {
      name: PaymentTypes.Disputes,
      value: disputes.amount,
      loading: disputes.loading,
      failed: disputes.failed,
      visible: true,
      isAmount: true,
    },
    {
      name: PaymentTypes.Failed,
      value: failed.amount,
      loading: failed.loading,
      failed: failed.failed,
      visible: true,
      isAmount: false,
    },
  ];
};

export const getPaymentMethodLabel = (method: string): string => {
  return paymentMethodOptionsMap[method] ?? titleCase(method);
};

export const getPaymentMethodData = (
  paymentByMethod: SplitPaymentMethod[],
): PaymentSplitMethodData => {
  const labels: string[] = [];
  const segmentData: number[] = [];
  let segmentDataTotal = 0;
  paymentByMethod.forEach(({ label, value }) => {
    labels.push(getPaymentMethodLabel(label));
    segmentData.push(value);
    segmentDataTotal += value;
  });
  return {
    labels,
    segmentData,
    segmentDataTotal,
  };
};
