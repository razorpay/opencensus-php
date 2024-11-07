import { AlertProps } from '@razorpay/blade/components';
import { OpenModalType } from 'common/typings';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import {
  getAnalyticsProductsInfo,
  scrollToPaypalSection,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import {
  BannerType,
  ICProductStates,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import React from 'react';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { BannerProps } from './Banner';

type Data = Required<Omit<BannerProps, 'ppliProductState' | 'pgProductState'>> & {
  openModal: OpenModalType;
  history: RouteComponentProps['history'];
};

export const updateWebsitePath = '/website-app-settings/business-website-details';
export const updateWebsitePathWithCta =
  '/website-app-settings/business-website-details?action=update-website-details';

const commonData = {
  isFullWidth: true,
  isDismissible: false,
};

const contactSupport = {
  onClick: (): void => {
    trackIEEvent({
      objectName: 'Under Review Banner Contact Support',
      actionName: 'Clicked',
    });
    CreateTicketEmitter.emit('create-ticket', 'tickets');
  },
  text: 'Contact support',
};

const underReviewTimeBreachedAgainActions = {
  actions: {
    primary: {
      ...contactSupport,
    },
  },
};

const ncActions = ({ openModal }: { openModal: OpenModalType }) => ({
  actions: {
    primary: {
      onClick: (): void => {
        trackIEEvent({
          objectName: 'Submit Details Now',
          actionName: 'Clicked',
        });
        openModal({
          size: 'large',
          component: (
            <BankAccountUpdateFlow
              defaultView={BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION}
              workflowType={WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI}
              workflowName="international card payment request"
              isMultiple={true}
              isFileUploadRequried={false}
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

export const getBannerProps = ({
  type,
  openModal,
  workflowEta,
  bannerMessage = '',
  history,
}: Data): AlertProps => {
  const {
    APPROVED,
    UNDER_REVIEW,
    UNDER_REVIEW_BREACHED,
    UNDER_REVIEW_BREACHED_AGAIN,
    REJECTED,
    NEEDS_CLARIFICATION,
    WEBSITE_DETAIL_UPDATE,
  } = BannerType;

  switch (type) {
    case APPROVED:
      return {
        title: 'Your request to activate international card payments was successful',
        description: bannerMessage || '',
        color: 'positive',
        ...commonData,
      };
    case UNDER_REVIEW:
      return {
        title: 'Your request to activate international card payments is under review',
        description: `We'll verify your details in a few days and share an update by ${workflowEta}.`,
        color: 'information',
        ...commonData,
      };
    case UNDER_REVIEW_BREACHED:
      return {
        title: 'Your request to activate international card payments is under review (Delayed)',
        description: `This is taking more time than usual. We'll verify your details in a few days and share an update by ${workflowEta}.`,
        color: 'information',
        ...commonData,
      };
    case UNDER_REVIEW_BREACHED_AGAIN:
      return {
        title: 'Your request to activate international card payments is under review (Delayed)',
        description:
          "This is taking more time than usual. We'll verify your details in a few days and share an update soon. To know more, contact our support.",
        color: 'information',
        ...commonData,
        ...underReviewTimeBreachedAgainActions,
      };
    case NEEDS_CLARIFICATION:
      return {
        title: 'We need a few more details for your international cards payment request',
        description:
          "Please note, you'll be able to collect international card payments only after verification is complete",
        color: 'notice',
        ...commonData,
        ...ncActions({ openModal }),
      };

    case REJECTED:
      return {
        title: 'Your request to activate international card payments is rejected',
        description: bannerMessage || '',
        color: 'negative',
        ...commonData,
        actions: {
          primary: {
            onClick: () => {
              scrollToPaypalSection(history);
              trackIEEvent({
                objectName: 'Link PayPal Now',
                actionName: 'Clicked',
              });
            },
            text: 'Link PayPal account',
          },
        },
      };
    case WEBSITE_DETAIL_UPDATE:
      return {
        description:
          'Update your website details to request for international card payments on payment gateway',
        color: 'notice',
        ...commonData,
        actions: {
          primary: {
            onClick: () => {
              trackIEEvent({
                objectName: 'Update Website Details',
                actionName: 'Clicked',
                subSection: 'Info Form',
              });
              history.push(updateWebsitePathWithCta);
            },
            text: 'Update',
          },
        },
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

export const trackBannerDisplayed = ({
  type,
  bannerMessage,
  pgProductState,
  ppliProductState,
}: {
  type: BannerType;
  bannerMessage: string | null;
  pgProductState: ICProductStates;
  ppliProductState: ICProductStates;
}): void => {
  const {
    APPROVED,
    UNDER_REVIEW,
    UNDER_REVIEW_BREACHED,
    UNDER_REVIEW_BREACHED_AGAIN,
    REJECTED,
    NEEDS_CLARIFICATION,
  } = BannerType;
  let trackBannerType = '';
  const properties: Record<string, string> = {};
  switch (type) {
    case APPROVED:
      trackBannerType = 'Success';
      break;
    case UNDER_REVIEW:
      trackBannerType = 'Under Review';
      properties.review_state = 'Normal';
      break;
    case UNDER_REVIEW_BREACHED:
    case UNDER_REVIEW_BREACHED_AGAIN:
      trackBannerType = 'Under Review';
      properties.review_state = 'Delayed';
      break;
    case REJECTED:
      if (bannerMessage) {
        trackBannerType = 'Rejected';
        properties.rejection_period = bannerMessage.includes('Please submit a new request after')
          ? 'Wait for 90 days'
          : 'Apply now';
      }
      break;
    case NEEDS_CLARIFICATION:
      trackBannerType = 'Needs Clarification';
      break;
    default:
      trackBannerType = '';
  }

  if (trackBannerType) {
    properties.products = getAnalyticsProductsInfo(pgProductState, ppliProductState, type);

    trackIEEvent({
      objectName: `${trackBannerType} Banner`,
      actionName: 'Displayed',
      properties,
    });
  }
};
