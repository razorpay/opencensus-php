import {
  ALL_LABEL,
  ALL_VALUE,
  durationOptionsMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { generateOptions } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

export const disputeDurationOptionsMap = {
  ...durationOptionsMap,
};
export const disputeDurationSectionOptions = generateOptions(disputeDurationOptionsMap);
export const disputeDurationSectionName = 'Duration';
export const disputeDurationOptions = [
  {
    section: {
      name: disputeDurationSectionName,
      options: disputeDurationSectionOptions,
    },
  },
];

export const statusOptionsMap = {
  [ALL_VALUE]: ALL_LABEL,
  open: 'Open',
  under_review: 'Under Review',
  lost: 'Lost',
  won: 'Won',
  closed: 'Closed',
};
export const statusSectionOptions = generateOptions(statusOptionsMap);
export const statusSectionName = 'Status';
export const statusOptions = [
  {
    section: {
      name: statusSectionName,
      options: statusSectionOptions,
    },
  },
];

export const searchByOptionsMap = {
  id: 'Dispute ID',
  payment_id: 'Payment ID',
};
export const searchBySectionOptions = generateOptions(searchByOptionsMap);
export const searchBySectionName = 'Search by';
export const searchByOptions = [
  {
    section: {
      name: searchBySectionName,
      options: searchBySectionOptions,
    },
  },
];

export const disputePhaseMap = {
  Retrieval: 'retrieval',
  Chargeback: 'chargeback',
  'Pre-arbitration': 'pre_arbitration',
  Arbitration: 'arbitration',
};

export const disputeReasonsMap = {
  Fraudulent: 'fraudulent',
  Service: 'service',
  'Processing Errors': 'processing_errors',
  Authorization: 'authorization',
};

export const paymentTypeMap = {
  Domestic: '0',
  International: '1',
};

export enum FILTER_TITLE {
  PHASES_OF_DISPUTE = 'Phases of dispute',
  REASONS_OF_DISPUTE = 'Reasons of dispute',
  PAYMENT_TYPE = 'Payment type',
}

export const disputeFilters = {
  [FILTER_TITLE.PHASES_OF_DISPUTE]: Object.keys(disputePhaseMap),
  [FILTER_TITLE.PAYMENT_TYPE]: Object.keys(paymentTypeMap),
};

export const filterTitleMap = {
  [FILTER_TITLE.PHASES_OF_DISPUTE]: 'phase',
  [FILTER_TITLE.REASONS_OF_DISPUTE]: 'reason_category',
  [FILTER_TITLE.PAYMENT_TYPE]: 'international',
};

export const filterTagMap = {
  [FILTER_TITLE.PHASES_OF_DISPUTE]: 'Phase',
  [FILTER_TITLE.REASONS_OF_DISPUTE]: 'Reason',
  [FILTER_TITLE.PAYMENT_TYPE]: 'Nationality',
};

export const filterValuesMap = {
  ...paymentTypeMap,
  ...disputeReasonsMap,
  ...disputePhaseMap,
};

export const DISPUTES_NO_DATA_FOUND_TEXT =
  'No disputes found for the selected duration and criteria!';

export const DISPUTE_DOCS_LINK = 'https://razorpay.com/docs/payments/disputes/';

export const DISPUTE_INFO_TEXT =
  'A dispute is a situation that arises when your customer or the issuing bank questions the validity of payment. It could arise due to reasons such as unauthorised charges,failure to deliver promised merchandise, excessive charges and so on.';

export const disputesStatusVariantMap = {
  won: {
    variant: 'positive',
    content: 'Won',
  },
  lost: {
    variant: 'neutral',
    content: 'Lost',
  },
  open: {
    variant: 'negative',
    content: 'Open',
  },
  under_review: {
    variant: 'notice',
    content: 'Under review',
  },
  closed: {
    variant: 'information',
    content: 'Under review',
  },
} as const;
