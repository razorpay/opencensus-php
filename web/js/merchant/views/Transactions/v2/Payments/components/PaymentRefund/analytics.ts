import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { getLabelForRefundDefaultSpeed } from './utils';

export const sendAnalyticsOnLoad = ({ payment, defaultRefundSpeed, transfers, hasEnoughFunds }) => {
  if (payment?.id) {
    analyticsTrack({
      objectName: 'refund amount popup',
      actionName: 'rendered',
      screen: 'home page',
      properties: {
        paymentId: payment.id,
        paymentMethod: payment.method,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  if (!hasEnoughFunds) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Issue Refund',
      eventLabel: `Add Funds | Default speed ${getLabelForRefundDefaultSpeed(defaultRefundSpeed)}`,
    });
  }

  if (!payment?.instant_refund_support) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Issue Refund',
      eventLabel: `Instant Refund not supported | Default speed ${getLabelForRefundDefaultSpeed(
        defaultRefundSpeed,
      )}`,
    });
  }

  if (transfers?.items?.length) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Issue Refund',
      eventLabel: `Route transfer | Default speed ${getLabelForRefundDefaultSpeed(
        defaultRefundSpeed,
      )}`,
    });
  }
};

export const trackRefundAnalyticsOnSuccess = ({
  defaultRefundSpeed,
  isPartial,
  comment,
  isInstantRefundChecked,
}) => {
  const isNormalSpeed = defaultRefundSpeed === 'normal';
  const isInstantSpeed = !isNormalSpeed;
  const refundType = isPartial ? 'Partial' : 'Full';
  const commentLabel = comment ? ' | Add Comment' : '';
  const checkboxLabel =
    isInstantRefundChecked !== null
      ? isInstantRefundChecked
        ? ' | Checked Checkbox'
        : ' | Unchecked Checkbox'
      : '';
  const speedLabel = isNormalSpeed ? ' | Default Speed Normal' : ' | Default Speed Instant';

  const label = `${refundType} Refund${commentLabel}${checkboxLabel}${speedLabel}`;

  if (
    (isPartial && comment && isNormalSpeed) ||
    (isPartial && comment && isInstantSpeed) ||
    (isPartial && comment && isInstantSpeed && !isInstantRefundChecked)
  ) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Partial Refund',
      eventLabel: label,
    });
  }

  if (
    (isPartial && !comment && isNormalSpeed) ||
    (isPartial && !comment && isInstantSpeed) ||
    (isPartial && !comment && isInstantSpeed && !isInstantRefundChecked)
  ) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Partial Refund',
      eventLabel: label,
    });
  }

  if (!isPartial && comment && isNormalSpeed) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Full Refund',
      eventLabel: label,
    });
  }

  if (!isPartial && !comment && isNormalSpeed) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Full Refund',
      eventLabel: label,
    });
  }

  if (!isPartial && comment && isInstantSpeed) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Full Refund',
      eventLabel: label,
    });
  }

  if (!isPartial && !comment && isInstantSpeed) {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Full Refund',
      eventLabel: label,
    });
  }
};

export const trackRefundAnalyticsOnRefundClick = ({ speedValue, payment, defaultRefundSpeed }) => {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - Instant Refund',
    eventAction: 'Yes Refund',
    eventLabel: `${
      speedValue === 'normal' ? 'Normal' : 'Instant'
    } Refund | Default speed ${getLabelForRefundDefaultSpeed(defaultRefundSpeed)} `,
  });

  analyticsTrack({
    objectName: 'issue refund',
    actionName: 'clicked',
    screen: 'transactions',
    properties: {
      paymentId: payment.id,
      paymentMethod: payment.method,
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};

export const trackOnFetchingRefundFee = ({ speedRequested, payment }) => {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - Payments',
    eventAction: 'Refund - Payment',
    eventLabel: `payment_id=${payment.id}`,
    speed_requested: speedRequested,
  });
};

export const trackInstantRefundCheckbox = (payment, checked) => {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - Payments',
    eventAction: checked ? 'Checked - Instant Refund' : 'Unchecked - Instant Refund',
    eventLabel: `payment_id=${payment.id}`,
  });
};

export const trackRefundError = (payment, errors) => {
  analyticsTrack({
    objectName: 'issue refund',
    actionName: 'response',
    screen: 'payments',
    toLumberjack: true,
    properties: {
      status: 'error',
      paymentMethod: payment.method,
      paymentId: payment.id,
      error: Array.isArray(errors) ? errors.join(',') : JSON.stringify(errors),
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};
