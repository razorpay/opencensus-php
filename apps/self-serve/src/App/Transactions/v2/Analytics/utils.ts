import moment from 'moment';

import type { Options, Option } from '@libs/web-nexus/common/components/Dropdown/types';
import { DASHBOARD_MODE } from '@libs/shared-types';
import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import { toTitleCase } from '@libs/shared-utils';
import {
  AnalyticsBoilerPlateData,
  BottomOverviewCardData,
  BottomOverviewCardsData,
  FailedOverviewResult,
  PaymentSplitMethodData,
  PaymentTypes,
  RefundResponse,
  SplitPaymentMethod,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { paymentMethodOptionsMap } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter/constants';
import {
  TransactionsEntityRoute,
  durationOptionsMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { Duration, DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import { generateOptions, getFromTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

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

export const isSrEnabledForUser = ({ mode, user }: { mode: DASHBOARD_MODE; user: PaymentsDashboardUser }): boolean => {
  return !!(
    mode === 'live' &&
    user.findTag('success_rate') &&
    user.isAllowedView &&
    user.isAllowedView('success_rate')
  );
};

export const getLandingPageAnalyticsToolTip = (organizationName = 'Razorpay') => ({
  Collected: `This is the amount collected in your ${organizationName} balance and will be deposited in your bank account after deductions and adjustments as per your settlement cycle`,
  [PaymentTypes.Refunds]: `This is the amount reversed to customer's bank account. To create and cancel refunds in bulk, go to Batch refunds`,
  [PaymentTypes.Disputes]: `This is the amount adjusted for customer raised issues such as unauthorised charges or failure to deliver the promised merchandise`,
  [PaymentTypes.Failed]: `These are payments that were unsuccessful due to technical, network, bank, business, or customer related issues (they will need to be retried by the customer) `,
});

export const getEntityPageAnalyticsToolTip = (organizationName = 'Razorpay') => ({
  Refunds: {
    refunded: `${organizationName} has completed the refund. After this, bank can take 5-7 working days to credit the amount to customer(s) account`,
    processing: `${organizationName} is attempting to complete the refund. It can take upto 3-5 working days`,
    failed: 'Due to customer(s) account error or bank-related issues',
  },
  Failed: {
    failed:
      'These are payments that were unsuccessful due to technical, network, bank, business, or customer related issues (they will need to be retried by the customer)',
    customerDroppOff:
      'These payment failures may happen due to incorrect card details or OTP, insufficient bank balance, or paymentcancellation by the customer',
    bankingFailures:
      "These failures happen due to technical or system issues at the customer's bank end",
    businessFailures:
      'These failures may happen due to technical issues from your end such as non-activation of a paymentmethod or international payments',
  },
});

export const getAnalyticsPropsForRefunds = (
  refundsData: RefundResponse,
  organizationName = 'Razorpay',
): AnalyticsBoilerPlateData => {
  const { failed, processing, refunded } = refundsData;
  return {
    lead: {
      title: 'Refunded',
      value: refunded.amount,
      isAmount: true,
      subtitle: `from ${refunded.count} processed refunds`,
      toolTipText: getEntityPageAnalyticsToolTip(organizationName).Refunds.refunded,
    },
    trail: [
      {
        title: 'Processing',
        value: processing.amount,
        isAmount: true,
        subtitle: `from ${processing.count} refunds`,
        toolTipText: getEntityPageAnalyticsToolTip(organizationName).Refunds.processing,
      },
      {
        title: 'Failed',
        value: failed.amount,
        isAmount: true,
        subtitle: `from ${failed.count} refunds`,
        toolTipText: getEntityPageAnalyticsToolTip(organizationName).Refunds.failed,
      },
    ],
  };
};

export const getAnalyticsPropsForFailedPyaments = (
  failedPaymentsData: number,
  failureInfo: FailedOverviewResult,
  organizationName = 'Razorpay',
): AnalyticsBoilerPlateData => {
  return {
    lead: {
      title: 'Failed',
      value: failedPaymentsData,
      subtitle: 'payments',
      toolTipText: getEntityPageAnalyticsToolTip(organizationName).Failed.failed,
    },
    trail: [
      {
        title: 'Bank-Related',
        subtitle: 'payments',
        value: failureInfo.bank.value,
        toolTipText: getEntityPageAnalyticsToolTip(organizationName).Failed.bankingFailures,
      },
      {
        title: 'Customer drop-offs',
        value: failureInfo.customer.value,
        subtitle: 'payments',
        toolTipText: getEntityPageAnalyticsToolTip(organizationName).Failed.customerDroppOff,
      },
      {
        title: 'Business failures or others',
        value: failureInfo.others.value + failureInfo.business.value,
        subtitle: 'payments',
        toolTipText: getEntityPageAnalyticsToolTip(organizationName).Failed.businessFailures,
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
  return paymentMethodOptionsMap[method] ?? toTitleCase(method);
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
