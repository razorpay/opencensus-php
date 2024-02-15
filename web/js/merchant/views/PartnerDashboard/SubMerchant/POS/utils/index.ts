import moment from 'moment';

import {
  actionStateType,
  clarificationReasonsType,
  kycHistoryObjectType,
  PosSubmerchantDetailsResponseDataType,
} from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';

export const formatDate = (date: number | undefined, format: string): string => {
  return date ? moment(date * 1000).format(format) : 'N/A';
};

// reasons will be selected between timestamp of the previous needs_clarification status and current
// previousNCTime is used to keep track of the previous needs_clarification status

const getClarificationReasons = (
  previousNCTime: number,
  currentIndex: number,
  actionState: actionStateType,
  clarificationReasons: clarificationReasonsType,
) => {
  return Object.entries(clarificationReasons)
    .map(([key, value]) => {
      let reason = '';
      value.forEach((item) => {
        if (
          item.created_at < actionState[currentIndex].created_at &&
          item.created_at > previousNCTime
        ) {
          reason = item.reason_code;
        }
      });
      return { field: key, reason };
    })
    .filter((item) => item.reason !== '');
};

// parse the data for KYC history component
// returns an array of objects with status, date, performedBy, and reason fields
// reason field is optional and is only present for needs_clarification status

export const parseKycHistoryData = (
  actionState: actionStateType | undefined,
  clarificationReasons: clarificationReasonsType | undefined = {},
): kycHistoryObjectType[] | [] => {
  if (!actionState) return [];

  const kycHistory = actionState.map((item) => {
    const response: kycHistoryObjectType = {
      status: item.name,
      date: formatDate(item.created_at, 'MMM DD hh:mma'),
      performedBy: item.metadata.actor_name,
    };
    return response;
  });

  if (!clarificationReasons) return kycHistory;
  let previousNCTime = -1;

  const kycHistoryWithReasons = actionState.map((item, index) => {
    const response = kycHistory[index];
    if (response.status === 'needs_clarification') {
      const responseWithReason = {
        ...response,
        reason: getClarificationReasons(previousNCTime, index, actionState, clarificationReasons),
      };
      previousNCTime = item.created_at;
      return responseWithReason;
    }
    return response;
  });
  return kycHistoryWithReasons;
};

// ActionKycButton Logic
export const getKycActionButtonState = ({
  submerchant,
  sendKYCRequest,
  openKYCForm,
  isSubmerchantKYCAccess,
}: {
  submerchant: PosSubmerchantDetailsResponseDataType;
  sendKYCRequest: () => void;
  openKYCForm: () => void;
  isSubmerchantKYCAccess: boolean;
}): {
  buttonText: string;
  onClickAction: () => void;
  isHidden: boolean;
  isKycRejected: boolean;
} => {
  const kyc_access = submerchant?.kyc_access;
  const activation_status = submerchant?.details?.activation_status;
  let kycAccessState = kyc_access?.state;
  const token_expiry = kyc_access?.token_expiry ? moment.unix(kyc_access?.token_expiry) : null;
  const rejection_count = kyc_access?.rejection_count;
  let isHidden = false;
  let buttonText = 'Request for KYC';
  let isKycRejected = false;

  let onClickAction = sendKYCRequest;
  const currentTime = moment();
  const isApprovalRequestExpired = currentTime.isAfter(token_expiry);
  if (kycAccessState === 'pending_approval' && isApprovalRequestExpired) {
    kycAccessState = 'expired';
  }

  if (kycAccessState === 'pending_approval') {
    isHidden = true;
  }
  if (kycAccessState === 'approved') {
    buttonText = 'Perform KYC';
    onClickAction = openKYCForm;
  }
  if (kycAccessState === 'rejected') {
    buttonText = 'Resend KYC request';
    if (rejection_count && rejection_count >= 3) {
      isKycRejected = true;
    }
  }
  if (activation_status === 'needs_clarification') {
    buttonText = 'Resubmit KYC details';
    onClickAction = openKYCForm;
  }
  if (
    [
      'activated',
      'activated_mcc_pending',
      'under_review',
      'kyc_qualified_unactivated',
      'rejected',
    ].includes(activation_status)
  ) {
    isHidden = true;
  }
  if (isSubmerchantKYCAccess) {
    buttonText = 'Perform KYC';
    onClickAction = openKYCForm;
  }
  if (isKycRejected) {
    buttonText = 'Request Not Accepted';
  }
  return {
    buttonText,
    onClickAction,
    isHidden,
    isKycRejected,
  };
};
