import React from 'react';
import { SEAMLESS_PROVIDERS } from 'shell/Navigator/constants';
import { titleCase, getFormattedAmount } from '@dashboard/shared-utils/rzp-utils';
import copyToClipboard from '@dashboard/shared-utils/copyToClipboard';
import { Theme, BadgeProps, Text, Link, ChevronRightIcon } from '@razorpay/blade/components';
import Lottie from 'react-lottie';
import moment from 'moment';
import {
  IPaymentDetails,
  PaymentStatus,
  IPaymentIdRefundDetail,
  IBankTransfer,
  DisputeStatus,
} from './types';
import {
  trackDetailsCopy,
  trackDetailsClick,
} from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { TimelineJourneyPoint } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/Timeline/types';
import AuthorizedAnimationData from 'apps/self-serve/src/App/Transactions/v2/Payments/lottie/Authorized';
import CapturedAnimationData from 'apps/self-serve/src/App/Transactions/v2/Payments/lottie/Captured';
import CreatedAnimationData from 'apps/self-serve/src/App/Transactions/v2/Payments/lottie/Created';
import FailedAnimationData from 'apps/self-serve/src/App/Transactions/v2/Payments/lottie/Failed';
import RefundAnimationData from 'apps/self-serve/src/App/Transactions/v2/Payments/lottie/Refund';
import { POS_TRANSACTION_CHANNEL } from 'apps/self-serve/src/App/Transactions/v2/common/constants';

export const shouldHideCapturePaymentAction = (
  payment: IPaymentDetails,
  bankTransfer: IBankTransfer | null,
) => {
  const method = payment?.method;
  const isAccountClosed = bankTransfer?.virtual_account?.status === 'closed';
  return method === 'bank_transfer' && isAccountClosed; //hideActions
};

export const isGatewaySupportingRefund = (payment: IPaymentDetails) =>
  payment?.gateway_refund_support;

export const isPaymentThroughSeamlessProviders = (payment: IPaymentDetails): boolean => {
  return (
    SEAMLESS_PROVIDERS.includes(payment?.optimizer_provider) && !payment?.gateway_refund_support
  );
};

export const isPaymentEligibleForRefundAsPerStatus = (payment: IPaymentDetails): boolean => {
  // these payments can't be refunded, only captured payments can be refunded
  if (
    [
      PaymentStatus.CREATED,
      PaymentStatus.AUTHENTICATED,
      PaymentStatus.AUTHORIZED,
      PaymentStatus.FAILED,
      PaymentStatus.REFUNDED,
    ].includes(payment.status)
  ) {
    return false;
  }

  return true;
};

export const isPaymentEligibleForRefund = (
  payment: IPaymentDetails,
  user: Record<string, any>,
): boolean => {
  return (
    payment?.method !== 'cod' &&
    !user.isOrgFeatureExist('block_payment_refund') &&
    user.isRefundAllowed &&
    (user.isOrgAllowedFunctionality('card_refunds') || !['card', 'emi'].includes(payment?.method))
  );
};

export const hasPaymentOpenNonFraudDisputes = (payment: IPaymentDetails) => {
  const hasOpenNonFraudDisputes =
    payment?.disputes?.items?.filter(
      ({ status, phase }) =>
        [DisputeStatus.OPEN, DisputeStatus.UNDER_REVIEW].includes(status) && phase !== 'fraud',
    )?.length ?? 0;

  if (hasOpenNonFraudDisputes) return true;

  return false;
};

export const isIssueRefundDisabledForMethod = (
  payment: IPaymentDetails,
  user: Record<string, any>,
) => {
  const { isUpiRefundDisabled, isNetbankingRefundDisabled, isCardRefundDisabled } = user;
  switch (payment.method) {
    case 'upi':
      return isUpiRefundDisabled;
    case 'netbanking':
      return isNetbankingRefundDisabled;
    case 'card':
      return isCardRefundDisabled;
    default:
      return false;
  }
};

export const isIssueRefundDisabled = (payment: IPaymentDetails, user: Record<string, any>) => {
  return (
    !isGatewaySupportingRefund(payment) ||
    !isPaymentEligibleForRefundAsPerStatus(payment) ||
    hasPaymentOpenNonFraudDisputes(payment) ||
    !isPaymentEligibleForRefund(payment, user) ||
    isPaymentThroughSeamlessProviders(payment) ||
    isIssueRefundDisabledForMethod(payment, user)
  );
};

const getRefundTimestamp = (refund: IPaymentIdRefundDetail): number | null => {
  switch (refund.status) {
    case 'created':
      return refund.created_at;
    case 'processed':
      return refund.processed_at;
    default:
      return null;
  }
};

export const getSettlementTimelineData = (payment: IPaymentDetails): TimelineJourneyPoint[] => {
  const settlement = payment?.transaction?.settlement;
  const settlementTimelineData: TimelineJourneyPoint[] = [];

  if (payment.transaction && settlement) {
    const data: { status: string; timestamp: number | null } = { status: '', timestamp: null };
    const settlementStatus = settlement.status;

    switch (settlementStatus) {
      case 'processed':
        data.status = settlementStatus;
        data.timestamp = payment?.transaction?.settled_at;
        break;
      case 'failed':
        data.status = settlementStatus;
        data.timestamp = null;
        break;
      default:
        data.status = 'created';
        data.timestamp = settlement?.created_at;
        break;
    }

    settlementTimelineData.push({
      id: `${settlement.id}`,
      entity: 'Settlement',
      status: data.status,
      title: 'Settlement',
      timestamp: data.timestamp,
      metadata: {
        statusInfo: titleCase(data.status),
        amount: settlement.amount,
        settlementId: settlement.id,
      },
    });
  }

  return settlementTimelineData;
};

export const getDisputesTimelineData = (payment: any): TimelineJourneyPoint[] => {
  const disputesTimelineData: TimelineJourneyPoint[] = [];

  if (payment && payment.disputes) {
    payment.disputes.items.forEach((dispute: any) => {
      disputesTimelineData.push({
        id: `dispute_${dispute.id}`,
        entity: 'Dispute',
        status: dispute.status,
        title: 'Dispute',
        timestamp: dispute.created_at,
        metadata: {
          statusInfo: titleCase(dispute.status),
          disputeId: dispute.id,
        },
      });
    });
  }

  return disputesTimelineData;
};

export const getRefundsTimelineData = (
  refunds: IPaymentIdRefundDetail[],
): TimelineJourneyPoint[] => {
  const refundTimelineData: TimelineJourneyPoint[] = [];

  if (refunds.length > 0) {
    refunds.forEach((refund) => {
      refundTimelineData.push({
        id: `refund_${refund.id}`,
        entity: 'Refund',
        status: refund.status,
        title: 'Refund',
        timestamp: getRefundTimestamp(refund),
        metadata: {
          statusInfo: titleCase(refund.status),
          refundId: refund.id,
          refund,
        },
      });
    });
  }

  return refundTimelineData;
};

export const getPaymentTimelineData = (
  payment: IPaymentDetails,
  paymentEventsData: any,
  bankTransferData: IBankTransfer | null,
): TimelineJourneyPoint[] => {
  const paymentIdTimelineData: TimelineJourneyPoint[] = [];

  // obvious state
  paymentIdTimelineData.push({
    id: 1,
    entity: 'Payment',
    status: 'created',
    title: 'Payment created',
    timestamp: payment.created_at,
    metadata: {},
  });

  if (payment.status === 'failed') {
    paymentIdTimelineData.push({
      id: 2,
      entity: 'Payment',
      status: 'failed',
      title: 'Payment failed',
      timestamp: null,
      metadata: {
        failureReason: payment?.error_description,
      },
    });
  } else {
    const isPaymentAuthorized = !!paymentEventsData.authorized_at;
    const isPaymentCaptured = !!paymentEventsData.captured_at;

    if (isPaymentAuthorized) {
      paymentIdTimelineData.push({
        id: 2,
        entity: 'Payment',
        status: 'authorized',
        title: 'Payment authorized',
        timestamp: paymentEventsData.authorized_at,
        metadata: {
          payment,
        },
      });
      if (isPaymentCaptured) {
        paymentIdTimelineData.push({
          id: 2,
          entity: 'Payment',
          status: 'captured',
          title: 'Payment captured',
          timestamp: paymentEventsData.captured_at,
          metadata: {
            payment,
          },
        });
      } else {
        if (
          shouldHideCapturePaymentAction(payment, bankTransferData) &&
          payment.status !== 'refunded'
        ) {
          paymentIdTimelineData.push({
            id: 2,
            entity: 'Payment',
            status: 'auth-failed',
            title: 'Payment failed',
            timestamp: null,
            metadata: {
              payment,
              failureReason: `This payment will be refunded within 72 hours`,
            },
          });

          return paymentIdTimelineData;
        }

        paymentIdTimelineData.push({
          id: 2,
          entity: 'Payment',
          status: 'not-captured',
          title: 'Payment captured',
          timestamp: null,
          metadata: {
            payment,
          },
        });
      }
    } else {
      paymentIdTimelineData.push({
        id: 2,
        entity: 'Payment',
        status: 'not-authorized',
        title: 'Payment authorized',
        timestamp: null,
        metadata: {
          payment,
        },
      });
    }
  }
  return paymentIdTimelineData;
};

/**
 *
 * source: web/js/apps/self-serve/src/App/Transactions/v1/Payments/components/PaymentDetails.js
 */
export const shouldShowCapturePaymentButton = (
  user: any,
  journeyPoint: TimelineJourneyPoint,
  bankTransfer: IBankTransfer,
): boolean => {
  const method = journeyPoint?.metadata?.payment?.method;

  return (
    user?.isAllowedEdit('payments') &&
    method !== 'intl_bank_transfer' &&
    !shouldHideCapturePaymentAction(journeyPoint?.metadata?.payment, bankTransfer)
  );
};

export const getAmountColor = (type: string, theme: Theme): string => {
  switch (type) {
    case 'positive':
      return `${theme.colors.feedback.text.positive.intense}`;
    case 'negative':
      return `${theme.colors.feedback.text.negative.intense}`;
    default:
      return `${theme.colors.surface.text.gray.normal}`;
  }
};

export const getBaseVariant = (status: IPaymentDetails['status']): BadgeProps['color'] => {
  switch (status) {
    case PaymentStatus.CREATED:
      return 'notice';
    case PaymentStatus.AUTHENTICATED:
    case PaymentStatus.AUTHORIZED:
      return 'neutral';
    case PaymentStatus.CAPTURED:
      return 'positive';
    case PaymentStatus.FAILED:
      return 'negative';
    case PaymentStatus.REFUNDED:
      return 'information';
    default:
      return 'neutral';
  }
};

export const getBadgeIcon = (status: IPaymentDetails['status']): JSX.Element => {
  let animationData = {};

  switch (status) {
    case PaymentStatus.CREATED:
      animationData = CreatedAnimationData;
      break;
    case PaymentStatus.AUTHENTICATED:
    case PaymentStatus.AUTHORIZED:
      animationData = AuthorizedAnimationData;
      break;
    case PaymentStatus.CAPTURED:
      animationData = CapturedAnimationData;
      break;
    case PaymentStatus.FAILED:
      animationData = FailedAnimationData;
      break;
    case PaymentStatus.REFUNDED:
      animationData = RefundAnimationData;
      break;
    default:
      break;
  }

  const lottieDefaultOptions = {
    loop: false,
    autoplay: true,
    animationData,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid slice',
    },
  };

  if (Object.keys(animationData).length === 0) return <div />;

  return <Lottie options={lottieDefaultOptions} />;
};

export const getTime = (timestamp: number): [string, string] => {
  const [createdDay, createdTime] = moment.unix(timestamp).format('ddd MMM D,hh:mma').split(',');
  return [createdDay, createdTime];
};

export const onCopy = (objectName: string, properties: any) => (id: string) => {
  copyToClipboard(id);
  trackDetailsCopy({
    objectName,
    properties,
  });
};

export const getRefundsOverviewDetails = (paymentRefundDetails: any) => {
  if (paymentRefundDetails.length === 0) return null;

  if (paymentRefundDetails.length === 1) {
    const refund = paymentRefundDetails[0];
    const createdAt = getTime(refund.created_at).join(', ');
    return (
      <Text color="surface.text.gray.normal" weight="semibold" size="small">
        Refund of ₹{getFormattedAmount(refund.amount, refund.currency)}issued on{createdAt}
      </Text>
    );
  } else {
    return (
      <Text color="surface.text.gray.normal" weight="semibold" size="small">
        Multiple refunds issued to the customer
      </Text>
    );
  }
};

export const getDisputesOverviewDetails = (paymentDetails: any, viewDisputeCallback: any) => {
  const disputes = paymentDetails.disputes.items;

  if (disputes.length === 0) return null;

  if (disputes.length === 1) {
    const dispute = disputes[0];
    const createdAt = getTime(dispute.created_at).join(', ');
    let info = `Refund of ₹${getFormattedAmount(
      dispute.amount,
      dispute.currency,
    )} issued on ${createdAt}`;

    switch (dispute.status) {
      case 'open':
        info = `Dispute of ₹${getFormattedAmount(
          dispute.amount,
          dispute.currency,
        )} initiated by the issusing bank.`;
        break;
      case 'closed':
        info = `Dispute of ₹${getFormattedAmount(
          dispute.amount,
          dispute.currency,
        )} has been closed`;
        break;
      case 'won':
        info = `You've won the chargeback for contesting dispute of ₹${getFormattedAmount(
          dispute.amount,
          dispute.currency,
        )}.`;
        break;
      case 'lost':
        info = `You've lost the chargeback for contesting dispute of ₹${getFormattedAmount(
          dispute.amount,
          dispute.currency,
        )}. The amount is being refunded to the customer`;
        break;
      case 'under_review':
        info = `Your documents are under review for contesting dispute of ₹${getFormattedAmount(
          dispute.amount,
          dispute.currency,
        )}.`;
        break;
      default:
        break;
    }
    return (
      <Text color="surface.text.gray.normal" weight="semibold" size="small">
        {info}{' '}
        <Link
          iconPosition="right"
          icon={ChevronRightIcon}
          variant="button"
          onClick={() => {
            trackDetailsClick({
              objectName: 'View Dispute Details',
              properties: {
                disputeStatus: dispute.status,
              },
            });
            viewDisputeCallback?.(`/disputes/${dispute.id}`);
          }}
          size="small"
        >
          View details
        </Link>
      </Text>
    );
  } else {
    return (
      <Text color="surface.text.gray.normal" weight="semibold" size="small">
        Multiple disputes exist for this payment
      </Text>
    );
  }
};

export const isPosTransaction = (source_channel?: string) => {
  if (source_channel === POS_TRANSACTION_CHANNEL) return true;
  return false;
};
