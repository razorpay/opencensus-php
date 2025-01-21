import { AlertProps } from '@razorpay/blade/components';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { hideWorkflowStatus } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import React from 'react';
import { BannerProps } from './Banner';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';
import {
  fireContactSupportFohEvent,
  showContactSupport,
  isRiskFoh,
  isRiskDisabled,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';
import { useFohTicket } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/store';
import { fetchFohTicketData } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';
import useFohTicketData from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/useFohTicketData';

export enum BannerType {
  SUCCESS = 'success',
  ACTIVE_SETTLEMENT_UNDER_REVIEW = 'active_settlement_under_review',
  ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED = 'active_settlement_under_review_time_breached',
  ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN = 'active_settlement_under_review_time_breached_again',
  INACTIVE_SETTLEMENT_UNDER_REVIEW = 'inactive_settlement_under_review',
  INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED = 'inactive_settlement_under_review_time_breached',
  INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN = 'inactive_settlement_under_review_time_breached_again',
  ACTIVE_SETTLEMENT_NC = 'active_settlement_nc',
  INACTIVE_SETTLEMENT_NC = 'inactive_settlement_nc',
  ACTIVE_SETTLEMENT_REJECTED = 'active_settlement_rejected',
  INACTIVE_SETTLEMENT_REJECTED = 'inactive_settlement_rejected',
  COMPLETE_KYC = 'complete_kyc',
  RISK_FOH = 'risk_foh',
  SOH = 'soh',
  BLOCK = 'block',
  SOH_CONTACT_SUPPORT = 'soh_contact_support',
}

export const isBankAccountReq = (type) => {
  return ![
    BannerType.COMPLETE_KYC,
    BannerType.INACTIVE_SETTLEMENT_REJECTED,
    BannerType.RISK_FOH,
    BannerType.SOH,
  ].includes(type);
};

type Data = Required<BannerProps>;

const commonData = {
  isFullWidth: true,
  isDismissible: false,
};

const contactSupport = (isFoh?: boolean, fohTicketId?: string, fohTicketStatus?: string) => ({
  onClick: (): void => {
    trackBankAccountUpdateEvent({
      objectName: 'Banner Contact Support',
      actionName: 'Clicked',
    });
    if (isFoh && showContactSupport(fohTicketStatus) && fohTicketId) {
      fireContactSupportFohEvent(fohTicketId);
    } else {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  },
  text: 'Contact support',
});

const underReviewTimeBreachedAgainActions = {
  actions: {
    primary: {
      ...contactSupport,
    },
  },
};

const ncActions = ({ openModal }: Pick<Data, 'openModal'>) => ({
  actions: {
    primary: {
      onClick: (): void => {
        trackBankAccountUpdateEvent({
          objectName: 'Banner Submit Details Now',
          actionName: 'Clicked',
        });
        openModal({
          size: 'large',
          component: (
            <BankAccountUpdateFlow
              defaultView={BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION}
              workflowType={WORKFLOW_TYPES.BANK_DETAIL_UPDATE}
              workflowName="Change your bank account"
            />
          ),
          overlayStyles: { padding: '12px' },
          className: 'bank-account-modal-layout',
        });
      },
      text: 'Submit details now',
    },
  },
});

export const data = ({ type, openModal, bankAccount, user, workflowEta }: Data): AlertProps => {
  const {
    SUCCESS,
    ACTIVE_SETTLEMENT_UNDER_REVIEW,
    ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
    ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
    INACTIVE_SETTLEMENT_UNDER_REVIEW,
    INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED,
    INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN,
    ACTIVE_SETTLEMENT_NC,
    INACTIVE_SETTLEMENT_NC,
    ACTIVE_SETTLEMENT_REJECTED,
    INACTIVE_SETTLEMENT_REJECTED,
    COMPLETE_KYC,
    SOH,
    RISK_FOH,
  } = BannerType;
  const { fohTicketId, fohTicketStatus } = useFohTicket();
  const { isLoading } = useFohTicketData();

  const maskedAccountNumber = bankAccount?.account_number
    ? `***${bankAccount.account_number.slice(-3)}`
    : '******';

  const onDismiss = (): void => {
    hideWorkflowStatus(user.id);
  };

  switch (type) {
    case SUCCESS:
      return {
        title: 'Your bank account change request was successful',
        description: `Settlements are now active on your account ending with ${maskedAccountNumber}`,
        color: 'positive',
        ...commonData,
        onDismiss,
      };
    case ACTIVE_SETTLEMENT_UNDER_REVIEW:
      return {
        title: 'Your bank account change request is under review',
        description: `We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements are currently active on your existing account ending with ${maskedAccountNumber} until then.`,
        color: 'information',
        ...commonData,
      };
    case ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED:
      return {
        title: 'Your bank account change request is under review (Delayed)',
        description: `This is taking more time than usual. We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements are currently active on your existing account ending with ${maskedAccountNumber} until then.`,
        color: 'information',
        ...commonData,
      };
    case ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN:
      return {
        title: 'Your bank account change request is under review (Delayed)',
        description: `This is taking more time than usual. We'll verify your details in a few days and share an update soon. To know more, contact our support. Please note, settlements are currently active on your existing account ending with ${maskedAccountNumber} until then.`,
        color: 'information',
        ...commonData,
        ...underReviewTimeBreachedAgainActions,
        actions: undefined,
      };
    case INACTIVE_SETTLEMENT_UNDER_REVIEW:
      return {
        title: 'Your bank account change request is under review',
        description: `We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements to your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        color: 'information',
        ...commonData,
      };
    case INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED:
      return {
        title: 'Your bank account change request is under review (Delayed)',
        description: `This is taking more time than usual. We'll verify your details in a few days and share an update by ${workflowEta}. Please note, settlements to your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        color: 'information',
        ...commonData,
      };
    case INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN:
      return {
        title: 'Your bank account change request is under review (Delayed)',
        description: `This is taking more time than usual. We'll verify your details in a few days and share an update soon. To know more, contact our support. Please note, settlements to your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        color: 'information',
        ...commonData,
        ...underReviewTimeBreachedAgainActions,
        actions: undefined,
      };
    case ACTIVE_SETTLEMENT_NC:
      return {
        title: 'We need a few more details for your bank account verification',
        description: `Please note, settlements are currently active on your bank account ending with ${maskedAccountNumber}`,
        color: 'notice',
        ...commonData,
        ...ncActions({ openModal }),
      };
    case INACTIVE_SETTLEMENT_NC:
      return {
        title: 'We need a few more details for your bank account verification',
        description: `Please note, settlements on your existing active account ending with ${maskedAccountNumber} are on-hold until your new bank account details are updated`,
        color: 'notice',
        ...commonData,
        ...ncActions({ openModal }),
      };
    case ACTIVE_SETTLEMENT_REJECTED:
      return {
        title: 'Your bank account change request is rejected',
        description: `The new bank account details you submitted couldn't be verified. Check your details and submit a new bank account change request to try again. Please note, settlements are currently active on your bank account ending with ${maskedAccountNumber}`,
        color: 'negative',
        ...commonData,
        onDismiss,
      };
    case INACTIVE_SETTLEMENT_REJECTED:
      return {
        title: 'Your bank account change request is rejected',
        description:
          "The new bank account details you submitted couldn't be verified. Check your details and submit a new bank account change request to try again. Please note, settlements will be on hold until your new bank account details are updated",
        color: 'negative',
        ...commonData,
        onDismiss,
      };
    case COMPLETE_KYC:
      return {
        title: 'Complete your KYC to receive payments in your bank account',
        description: 'Your KYC details are required to start settlements',
        color: 'negative',
        ...commonData,
        actions: {
          primary: {
            onClick: (): void => {
              trackBankAccountUpdateEvent({
                objectName: 'Banner Complete KYC',
                actionName: 'Clicked',
              });
              if (checkIfSignUpViaEasyOnboarding(window.rzp_user)) {
                window.location.href = window.EASY_ONBOARDING_URL;
              } else {
                window.location.href = 'https://dashboard.razorpay.com/app/activation';
              }
            },
            text: 'Complete KYC',
          },
        },
      };
    case RISK_FOH:
      return {
        title: 'Settlements for your Razorpay account are on hold',
        description:
          'We noticed unusual activity and have paused transfer of payments to your bank account. To resume settlements, contact our support for the next steps',
        color: 'negative',
        ...commonData,
        actions: {
          primary: {
            ...contactSupport(true, fohTicketId, fohTicketStatus),
          },
        },
      };
    case SOH:
      return {
        title: 'Update your bank account details to resume settlements',
        description:
          "Your settlements are on-hold as we've encountered a few issues with your given bank account. Please submit a request to change your bank account at the earliest",
        color: 'negative',
        ...commonData,
      };
    default:
      return {
        title: 'Banner type is not supported',
        description: 'Provide valid banner type',
        color: 'negative',
        ...commonData,
      };
  }
};

export const trackBannerDisplayed = (type: BannerType): void => {
  const {
    SUCCESS,
    ACTIVE_SETTLEMENT_NC,
    INACTIVE_SETTLEMENT_NC,
    ACTIVE_SETTLEMENT_REJECTED,
    INACTIVE_SETTLEMENT_REJECTED,
  } = BannerType;

  let trackBannerType = '';
  if (type === SUCCESS) {
    trackBannerType = 'Manual Verification Success';
  } else if (type === ACTIVE_SETTLEMENT_NC || type === INACTIVE_SETTLEMENT_NC) {
    trackBannerType = 'Manual Verification Clarification Required';
  } else if (type === ACTIVE_SETTLEMENT_REJECTED || type === INACTIVE_SETTLEMENT_REJECTED) {
    trackBannerType = 'Manual Verification Failure';
  }

  if (trackBannerType) {
    trackBankAccountUpdateEvent({
      objectName: `${trackBannerType} Banner`,
      actionName: 'Displayed',
    });
  }
};
