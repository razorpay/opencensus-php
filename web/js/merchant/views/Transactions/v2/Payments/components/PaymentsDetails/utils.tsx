import React from 'react';
import {
  Theme,
  BadgeProps,
  Text,
  Link,
  ChevronRightIcon,
  UserPlusIcon,
  CreditCardIcon,
  AlertTriangleIcon,
} from '@razorpay/blade/components';
import moment from 'moment';
import Lottie from 'react-lottie';

import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import copyToClipboard from 'common/utils/copyToClipboard';
import { titleCase, createI18nifyCurrencyFormattedString } from 'common/utils/rzp-utils';
import { isOrgFeatureExist } from 'merchant/models/User';
import { SEAMLESS_PROVIDERS } from 'merchant/views/Navigator/constants';
import { SettlementStatus } from 'merchant/views/Settlements/v3/typings';
import { POS_TRANSACTION_CHANNEL } from 'merchant/views/Transactions/constants';
import { TimelineJourneyPoint } from 'merchant/views/Transactions/v2/Payments/components/Timeline/types';
import AuthorizedAnimationData from 'merchant/views/Transactions/v2/Payments/lottie/Authorized';
import CapturedAnimationData from 'merchant/views/Transactions/v2/Payments/lottie/Captured';
import CreatedAnimationData from 'merchant/views/Transactions/v2/Payments/lottie/Created';
import FailedAnimationData from 'merchant/views/Transactions/v2/Payments/lottie/Failed';
import RefundAnimationData from 'merchant/views/Transactions/v2/Payments/lottie/Refund';
import RefundAnimationDataGeneric from 'merchant/views/Transactions/v2/Payments/lottie/RefundGeneric';
import {
  trackDetailsCopy,
  trackDetailsClick,
} from 'merchant/views/Transactions/v2/common/tracking';

import {
  IPaymentDetails,
  PaymentStatus,
  IPaymentIdRefundDetail,
  IBankTransfer,
  DisputeStatus,
  IQuestionDetails,
} from './types';

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
    !isOrgFeatureExist('block_payment_refund') &&
    user.isRefundAllowed &&
    (user.isOrgAllowedFunctionality('card_refunds') ||
      ['card', 'emi'].indexOf(payment?.method) === -1)
  );
};

export const hasPaymentOpenNonFraudDisputes = (payment: IPaymentDetails) => {
  const hasOpenNonFraudDisputes =
    payment?.disputes?.items?.filter(
      ({ status, phase }) =>
        [DisputeStatus.OPEN, DisputeStatus.UNDER_REVIEW].indexOf(status) > -1 && phase !== 'fraud',
    )?.length ?? 0;

  if (hasOpenNonFraudDisputes) return true;

  return false;
};

export const isIssueRefundDisabled = (payment: IPaymentDetails, user: Record<string, any>) => {
  return (
    !isGatewaySupportingRefund(payment) ||
    !isPaymentEligibleForRefundAsPerStatus(payment) ||
    hasPaymentOpenNonFraudDisputes(payment) ||
    !isPaymentEligibleForRefund(payment, user) ||
    isPaymentThroughSeamlessProviders(payment)
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

export const getDisputesTimelineData = (payment): TimelineJourneyPoint[] => {
  const disputesTimelineData: TimelineJourneyPoint[] = [];

  if (payment && payment.disputes) {
    payment.disputes.items.forEach((dispute) => {
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
  paymentEventsData,
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
        // For a payment that is refunded before being settled (void trxn), don't show the capture action.
        const isPaymentRefunded = payment?.status === 'refunded';
        const isPaymentSettled = payment?.transaction?.settled;

        if (isPaymentRefunded && !isPaymentSettled) {
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
 * source: web/js/merchant/views/Transactions/v1/Payments/components/PaymentDetails.js
 */
export const shouldShowCapturePaymentButton = (
  user,
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

export const getBaseVariant = (
  status: IPaymentDetails['status'] | SettlementStatus,
): BadgeProps['color'] => {
  switch (status) {
    case PaymentStatus.CREATED:
    case SettlementStatus.CREATED:
      return 'notice';
    case PaymentStatus.AUTHENTICATED:
    case PaymentStatus.AUTHORIZED:
    case SettlementStatus.INITIATED:
      return 'neutral';
    case PaymentStatus.CAPTURED:
    case SettlementStatus.PROCESSED:
      return 'positive';
    case PaymentStatus.FAILED:
    case SettlementStatus.FAILED:
      return 'negative';
    case PaymentStatus.REFUNDED:
      return 'information';
    default:
      return 'neutral';
  }
};

export const getBadgeIcon = (
  status: IPaymentDetails['status'] | SettlementStatus,
  isCountryIndia: boolean,
): JSX.Element => {
  let animationData = {};

  switch (status) {
    case PaymentStatus.CREATED:
    case SettlementStatus.CREATED:
      animationData = CreatedAnimationData;
      break;
    case PaymentStatus.AUTHENTICATED:
    case PaymentStatus.AUTHORIZED:
    case SettlementStatus.INITIATED:
      animationData = AuthorizedAnimationData;
      break;
    case PaymentStatus.CAPTURED:
    case SettlementStatus.PROCESSED:
      animationData = CapturedAnimationData;
      break;
    case PaymentStatus.FAILED:
    case SettlementStatus.FAILED:
      animationData = FailedAnimationData;
      break;
    case PaymentStatus.REFUNDED:
      animationData = isCountryIndia ? RefundAnimationData : RefundAnimationDataGeneric;
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

export const onCopy = (objectName, properties) => (id: string) => {
  copyToClipboard(id);
  trackDetailsCopy({
    objectName,
    properties,
  });
};

export const getRefundsOverviewDetails = (paymentRefundDetails) => {
  if (paymentRefundDetails.length === 0) return null;

  if (paymentRefundDetails.length === 1) {
    const refund = paymentRefundDetails[0];
    const createdAt = getTime(refund.created_at).join(', ');

    return (
      <Text color="surface.text.gray.normal" weight="semibold" size="small">
        Refund of {createI18nifyCurrencyFormattedString(refund.amount, refund.currency)} issued on{' '}
        {createdAt}
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

export const getDisputesOverviewDetails = (paymentDetails, viewDisputeCallback) => {
  const disputes = paymentDetails.disputes.items;

  if (disputes.length === 0) return null;

  if (disputes.length === 1) {
    const dispute = disputes[0];
    const createdAt = getTime(dispute.created_at).join(', ');
    let info = `Refund of ${createI18nifyCurrencyFormattedString(
      dispute.amount,
      dispute.currency,
    )} issued on ${createdAt}`;

    switch (dispute.status) {
      case 'open':
        info = `Dispute of ${createI18nifyCurrencyFormattedString(
          dispute.amount,
          dispute.currency,
        )} initiated by the issusing bank.`;
        break;
      case 'closed':
        info = `Dispute of ${createI18nifyCurrencyFormattedString(
          dispute.amount,
          dispute.currency,
        )} has been closed`;
        break;
      case 'won':
        info = `You've won the chargeback for contesting dispute of ${createI18nifyCurrencyFormattedString(
          dispute.amount,
          dispute.currency,
        )}.`;
        break;
      case 'lost':
        info = `You've lost the chargeback for contesting dispute of ${createI18nifyCurrencyFormattedString(
          dispute.amount,
          dispute.currency,
        )}. The amount is being refunded to the customer`;
        break;
      case 'under_review':
        info = `Your documents are under review for contesting dispute of ${createI18nifyCurrencyFormattedString(
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

export const isChargeSlipForPosEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { pos_chargeslip: undefined } };
  if (!abExperiments?.pos_chargeslip) return false;
  return isExperimentEnabled(abExperiments.pos_chargeslip);
};

export const getIsQuestionsApplicable = ({ status, method }: IPaymentDetails) => {
  const whiteListedMethods = ['upi', 'card'];
  return status === 'failed' && whiteListedMethods.includes(method);
};

export const getQuestionBody = (paymentDetails: IPaymentDetails): IQuestionDetails[] => {
  return [
    {
      id: 'payment-failed',
      icon: {
        type: AlertTriangleIcon,
        iconProps: {
          color: 'surface.icon.gray.subtle',
          size: 'medium',
        },
      },
      question: {
        value: 'Your payment has failed',
        props: {
          color: 'surface.text.gray.normal',
        },
      },
      answer:
        paymentDetails?.error_merchant_desc ||
        paymentDetails?.error_description ||
        'Your payment has failed due to a technical error.',
    },
    {
      id: 'money-implication',
      icon: {
        type: CreditCardIcon,
        iconProps: {
          color: 'surface.icon.gray.subtle',
          size: 'medium',
        },
      },
      question: {
        value: 'What happens to the money?',
        props: {
          color: 'surface.text.gray.normal',
        },
      },
      answer:
        paymentDetails?.error_money_implication ||
        'Any money deducted will be refunded within 7 working days',
    },
    {
      id: 'next-steps',
      icon: {
        type: UserPlusIcon,
        iconProps: {
          color: 'surface.icon.gray.subtle',
          size: 'medium',
        },
      },
      question: {
        value: 'What should I do next?',
        props: {
          color: 'surface.text.gray.normal',
        },
      },
      answer:
        paymentDetails?.error_next_step || 'Please advise your customer to retry the payment.',
    },
  ];
};

export const getHighlightDetails = ({ applicationDetails, createdDay, createdTime }) => {
  const highlights = [
    {
      title: 'Created on',
      value: `${createdDay}, ${createdTime}`,
    },
  ];
  if (applicationDetails?.name) {
    highlights.push({
      title: 'Payment initiated via',
      value: applicationDetails.name,
    });
  }
  return highlights;
};

const CUSTOMER = 'customer';
const BANK = 'bank';
const BUSINESS_AND_OTHERS = 'business_and_others';

export const FailureCategoryMapping = {
  customer: CUSTOMER,
  bank: BANK,
  gateway: BANK,
  issuer_bank: BANK,
  customer_psp: BANK,
  network: BANK,
  issuer: BANK,
  beneficiary_bank: BANK,
  business: BUSINESS_AND_OTHERS,
  merchant: BUSINESS_AND_OTHERS,
  provider: BUSINESS_AND_OTHERS,
  internal: BUSINESS_AND_OTHERS,
};

const CUSTOMER_DROP_OFF = 'Customer drop-offs';
const BANK_FAILURE = 'Bank-Related';
const BUSINESS_FAILURE = 'Business failures/Others';

export const FailureTypeMapping = {
  [CUSTOMER]: CUSTOMER_DROP_OFF,
  [BANK]: BANK_FAILURE,
  [BUSINESS_AND_OTHERS]: BUSINESS_FAILURE,
};

export const getStatusText = (
  paymentDetails: IPaymentDetails,
  isPaymentsRoute: boolean,
): string => {
  const { status, error_source } = paymentDetails;
  if (status === PaymentStatus.FAILED && isPaymentsRoute) {
    return `Payment Failed${
      error_source && FailureCategoryMapping[error_source]
        ? `: ${FailureTypeMapping[FailureCategoryMapping[error_source]]}`
        : ''
    }`;
  }
  return titleCase(status);
};

export const isPosTransaction = (source_channel?: string) => {
  if (source_channel === POS_TRANSACTION_CHANNEL) return true;
  return false;
};

export const imageDownload = ({
  base64EncodedImage,
  imageName,
}: {
  base64EncodedImage: string;
  imageName: string;
}) => {
  try {
    if (!base64EncodedImage || typeof base64EncodedImage !== 'string') {
      throw new Error('Invalid base64 encoded image string');
    }

    if (!imageName || typeof imageName !== 'string') {
      throw new Error('Invalid image name');
    }

    const prefix = 'data:image/png;base64,';
    if (!base64EncodedImage.startsWith(prefix)) {
      throw new Error('Base64 string does not contain the correct prefix');
    }

    const link = document.createElement('a');
    link.href = base64EncodedImage;
    link.download = `${imageName}.png`;
    document.body.appendChild(link);
    link.click();

    document.body.removeChild(link);
  } catch (error) {
    console.error('Image download failed:', (error as Error).message);
  }
};
