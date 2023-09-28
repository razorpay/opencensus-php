import {
  bannerTypeProductStatusMapping,
  DisabledInternationalCardsReasons,
  ICProductStates,
  InternationalCardsRejectionCodes,
  ProductTypeForAnalytics,
  ProductWorkflowStatesInBackend,
  BannerType,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import moment from 'moment';
import { ICEnablementWorkflowInfo, User } from 'common/typings';
import qs from 'query-string';
import { omit, isEmpty } from 'lodash';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

export const getIsInternationalCardsDisabledReason = ({
  user,
}: {
  user: User;
}): null | DisabledInternationalCardsReasons => {
  if (user) {
    if (user.international) {
      return null;
    } else if (user.isUnregisteredBusiness || user?.international_activation_flow === 'blacklist') {
      return DisabledInternationalCardsReasons.UNREGISTERED;
    } else if (!user.isAccepted) {
      return DisabledInternationalCardsReasons.NOT_ACTIVATED;
    } else if (user?.merchant?.hold_funds) {
      return DisabledInternationalCardsReasons.RISK_FOH;
    } else if (!user.business_website) {
      return DisabledInternationalCardsReasons.NO_WEBSITE_DETAILS;
    }
  }

  return null;
};

export const getWorkflowUnderReviewBannerAndEta = (
  workflowCreatedAt: number,
): { banner: BannerType; eta: string | null } => {
  let eta: string | null = null;
  let banner = BannerType.UNDER_REVIEW_BREACHED_AGAIN;
  const today = moment();
  const createdAt = moment.unix(workflowCreatedAt);
  const breachTime = moment(createdAt).add(2, 'days');
  const nextBreachTime = moment(createdAt).add(5, 'days');
  if (today.isBefore(breachTime)) {
    eta = moment(breachTime).format('MMM DD, YYYY');
    banner = BannerType.UNDER_REVIEW;
  } else if (today.isBefore(nextBreachTime)) {
    eta = moment(nextBreachTime).format('MMM DD, YYYY');
    banner = BannerType.UNDER_REVIEW_BREACHED;
  }
  return { banner, eta };
};

const workflowRejectionMapping = {
  [InternationalCardsRejectionCodes.CLARIFICATION_NOT_PROVIDED]:
    'Please add the required details and submit a new request again.',
  [InternationalCardsRejectionCodes.WEBSITE_DETAIL_INCOMPLETE]:
    'Please update the required pages on your website (About Us, Contact Us, Pricing of goods/services offered, Privacy Policy, Refund Policy, Terms and Conditions) and submit a new request again.',
  [InternationalCardsRejectionCodes.BUSINESS_MODEL_MISMATCH]:
    'Your website’s business type does not match the business type registered with us. Please update your website details in accordance with your registered business type and submit a new request again.',
  [InternationalCardsRejectionCodes.INVALID_DOCUMENTS]:
    "Your given details couldn't be verified. Please add the required details and submit a new request again.",
  [InternationalCardsRejectionCodes.RISK_REJECTION]:
    "Your given details couldn't be verified by our banking partners.",
  [InternationalCardsRejectionCodes.MERCHANT_HIGH_CHARGEBACKS_FRAUD_PRESENT]:
    "Your given details couldn't be verified by our banking partners.",
  [InternationalCardsRejectionCodes.DORMANT_MERCHANT]:
    "Your given details couldn't be verified by our banking partners as you don't have enough domestic transactions with us.",
  [InternationalCardsRejectionCodes.RESTRICTED_BUSINESS]:
    "Your given details couldn't be verified by our banking partners.",
};

export const getRejectionInfo = (
  workflowRejectionMessage: string,
  workflowRejectedAt: number,
): {
  reason: string;
  isRequestRejectedFor90Days: boolean;
} => {
  let reason = workflowRejectionMapping[workflowRejectionMessage];
  let isRequestRejectedFor90Days = false;
  if (
    [
      InternationalCardsRejectionCodes.RISK_REJECTION,
      InternationalCardsRejectionCodes.MERCHANT_HIGH_CHARGEBACKS_FRAUD_PRESENT,
      InternationalCardsRejectionCodes.DORMANT_MERCHANT,
      InternationalCardsRejectionCodes.RESTRICTED_BUSINESS,
    ].includes(workflowRejectionMessage as InternationalCardsRejectionCodes)
  ) {
    const today = moment();
    const workflowRejectedAtTime = moment.unix(workflowRejectedAt).add(90, 'days');
    const diffInDays = workflowRejectedAtTime.diff(today, 'days');
    if (diffInDays > 0) {
      isRequestRejectedFor90Days = true;
      reason += ` Please submit a new request after ${workflowRejectedAtTime.format(
        'LL',
      )} and try again.`;
    }
  }

  reason +=
    ' To collect international payments meanwhile, connect your Razorpay account with PayPal.';

  return { reason, isRequestRejectedFor90Days };
};

export const isNCState = (workflowInfo: ICEnablementWorkflowInfo): boolean =>
  !!workflowInfo.needs_clarification && !!workflowInfo.tags?.includes('awaiting-customer-response');

export const getProductState = ({
  backendProductState,
  workflowInfo,
  isRequestRejectedFor90Days,
}: {
  backendProductState: ProductWorkflowStatesInBackend;
  workflowInfo: ICEnablementWorkflowInfo;
  isRequestRejectedFor90Days: boolean;
}): ICProductStates | null => {
  switch (backendProductState) {
    case ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED:
      return ICProductStates.NOT_ACTIVATED;
    case ProductWorkflowStatesInBackend.IN_REVIEW:
      if (isNCState(workflowInfo)) {
        return ICProductStates.ACTION_REQUIRED;
      }
      return ICProductStates.UNDER_REVIEW;
    case ProductWorkflowStatesInBackend.APPROVED:
      return ICProductStates.ACTIVE;
    case ProductWorkflowStatesInBackend.REJECTED:
      if (!isRequestRejectedFor90Days) {
        return ICProductStates.NOT_ACTIVATED;
      }
      return ICProductStates.REJECTED;
    default:
      return null;
  }
};

export const getPreferredProduct = (products: string[]): ProductTypeForAnalytics | null => {
  const isPGPreferred = products.includes('payment_gateway');
  const isPpliPreferred = products.includes('invoices');

  if (isPGPreferred && isPpliPreferred) {
    return ProductTypeForAnalytics.All;
  } else if (isPGPreferred) {
    return ProductTypeForAnalytics.PG;
  } else if (isPpliPreferred) {
    return ProductTypeForAnalytics.PPLI;
  } else {
    return null;
  }
};

export const scrollToPaypalSection = (history: RouteComponentProps['history']): void => {
  const query = qs.parse(window.location.search);
  const pathname = window.location.pathname.replace('/app', '');

  const scrollToPaypal = (query) => {
    query.instrument = 'paypal';
    history.push({
      pathname,
      search: qs.stringify(query),
    });
  };

  if ('instrument' in query) {
    const queryParamWithoutSearch = omit(query, 'instrument');
    history.replace({
      pathname,
      search: isEmpty(queryParamWithoutSearch) ? '' : qs.stringify(queryParamWithoutSearch),
    });
    setTimeout(() => {
      scrollToPaypal(query);
    }, 50);
  } else {
    scrollToPaypal(query);
  }
};

export const getAnalyticsProductsInfo = (
  pgProductState: ICProductStates,
  ppliProductState: ICProductStates,
  type: BannerType,
): ProductTypeForAnalytics => {
  const statusToCheckFor = bannerTypeProductStatusMapping[type];
  const hasPGStatusMatched = pgProductState === statusToCheckFor;
  const hasPPLIStatusMatched = ppliProductState === statusToCheckFor;
  if (hasPGStatusMatched && hasPPLIStatusMatched) {
    return ProductTypeForAnalytics.All;
  } else if (hasPGStatusMatched) {
    return ProductTypeForAnalytics.PG;
  } else {
    return ProductTypeForAnalytics.PPLI;
  }
};
