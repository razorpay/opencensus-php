import { omit } from 'lodash';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import {
  getBannerProps,
  trackBannerDisplayed,
  updateWebsitePath,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner/config';
import { scrollToPaypalSection } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils';
import * as trackActions from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import {
  BannerType,
  ICProductStates,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import React from 'react';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils',
  () => ({
    scrollToPaypalSection: jest.fn(),
    getAnalyticsProductsInfo: jest.fn().mockReturnValue('All'),
  }),
);

const historyPush = jest.fn();

const commonArgs = {
  workflowEta: '23rd Feb, 2023',
  openModal: jest.fn(),
  bannerMessage: 'test banner message',
  history: {
    push: historyPush,
  },
};

const commonReturnData = {
  isFullWidth: true,
  isDismissible: false,
};
const trackIEEventSpy = jest.spyOn(trackActions, 'trackIEEvent');

describe('Banner config', () => {
  describe('getBannerProps', () => {
    test.each([
      [
        BannerType.APPROVED,
        {
          title: 'Your request to activate international card payments was successful',
          color: 'positive',
          useBannerMessageForDescription: true,
        },
        undefined,
      ],
      [
        BannerType.UNDER_REVIEW,
        {
          title: 'Your request to activate international card payments is under review',
          description: `We'll verify your details in a few days and share an update by ${commonArgs.workflowEta}.`,
          color: 'information',
        },
        undefined,
      ],
      [
        BannerType.UNDER_REVIEW_BREACHED,
        {
          title: 'Your request to activate international card payments is under review (Delayed)',
          description: `This is taking more time than usual. We'll verify your details in a few days and share an update by ${commonArgs.workflowEta}.`,
          color: 'information',
        },
        undefined,
      ],
      [
        BannerType.UNDER_REVIEW_BREACHED_AGAIN,
        {
          title: 'Your request to activate international card payments is under review (Delayed)',
          description:
            "This is taking more time than usual. We'll verify your details in a few days and share an update soon. To know more, contact our support.",
          color: 'information',
        },
        'Contact support',
      ],
      [
        BannerType.NEEDS_CLARIFICATION,
        {
          title: 'We need a few more details for your international cards payment request',
          description:
            "Please note, you'll be able to collect international card payments only after verification is complete",
          color: 'notice',
        },
        'Submit details now',
      ],
      [
        BannerType.WEBSITE_DETAIL_UPDATE,
        {
          description:
            'Update your website details to request for international card payments on payment gateway',
          color: 'notice',
        },
        'Update',
      ],
      [
        BannerType.REJECTED,
        {
          title: 'Your request to activate international card payments is rejected',
          useBannerMessageForDescription: true,
          color: 'negative',
        },
        'Link PayPal account',
      ],
      [
        '',
        {
          title: 'Banner type is not supported',
          description: 'Provide valid banner type',
          color: 'negative',
        },
        undefined,
      ],
    ])(
      'should return appropriate props when type is %s',
      (type, output: Record<string, unknown>, primaryBtnText) => {
        const expectedOutput: Record<string, unknown> = {
          ...commonReturnData,
          ...omit(output, 'useBannerMessageForDescription'),
        };
        if (output.useBannerMessageForDescription) {
          expectedOutput.description = commonArgs.bannerMessage;
        }

        const bannerProps = getBannerProps({
          ...commonArgs,
          // eslint-disable-next-line
          // @ts-ignore
          type,
        });

        expect(omit(bannerProps, 'actions', 'onDismiss')).toStrictEqual(expectedOutput);

        if (bannerProps.actions?.primary && primaryBtnText) {
          expect(bannerProps.actions.primary.text).toBe(primaryBtnText);
          bannerProps.actions.primary.onClick();
          // eslint-disable-next-line default-case
          switch (type) {
            case BannerType.UNDER_REVIEW_BREACHED_AGAIN:
              expect(trackIEEventSpy).toHaveBeenCalledWith({
                objectName: 'Under Review Banner Contact Support',
                actionName: 'Clicked',
              });
              expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
              break;
            case BannerType.NEEDS_CLARIFICATION:
              expect(trackIEEventSpy).toHaveBeenCalledWith({
                objectName: 'Submit Details Now',
                actionName: 'Clicked',
              });
              expect(commonArgs.openModal).toHaveBeenCalledWith({
                size: 'large',
                overlayStyles: { padding: '12px' },
                className: 'bank-account-modal-layout',
                component: (
                  <BankAccountUpdateFlow
                    defaultView={BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION}
                    workflowType={WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI}
                    workflowName="international card payment request"
                    isMultiple={true}
                    isFileUploadRequried={false}
                  />
                ),
              });
              break;
            case BannerType.REJECTED:
              expect(trackIEEventSpy).toHaveBeenCalledWith({
                objectName: 'Link Paypal Now',
                actionName: 'Clicked',
              });
              expect(scrollToPaypalSection).toHaveBeenCalledWith(commonArgs.history);
              break;
            case BannerType.WEBSITE_DETAIL_UPDATE:
              expect(trackIEEventSpy).toHaveBeenCalledWith({
                objectName: 'Update Website Details',
                actionName: 'Clicked',
                subSection: 'Info Form',
              });
              expect(historyPush).toHaveBeenCalledWith(
                `${updateWebsitePath}?action=update-website-details`,
              );
              break;
          }
        }
      },
    );
  });

  describe('trackBannerDisplayed', () => {
    test.each([
      ['Success', BannerType.APPROVED, '', {}],
      ['Under Review', BannerType.UNDER_REVIEW, null, { review_state: 'Normal' }],
      ['Under Review', BannerType.UNDER_REVIEW_BREACHED, null, { review_state: 'Delayed' }],
      ['Under Review', BannerType.UNDER_REVIEW_BREACHED_AGAIN, null, { review_state: 'Delayed' }],
      [
        'Rejected',
        BannerType.REJECTED,
        'Please submit a new request after May 22, 2023',
        { rejection_period: 'Wait for 90 days' },
      ],
      ['Rejected', BannerType.REJECTED, 'test', { rejection_period: 'Apply now' }],
      ['Needs Clarification', BannerType.NEEDS_CLARIFICATION, null, {}],
    ])(
      'should track %s banner when type is %s and banner message is %s',
      (eventName, type, bannerMessage, properties = {}) => {
        trackBannerDisplayed({
          type,
          bannerMessage,
          pgProductState: ICProductStates.ACTIVE,
          ppliProductState: ICProductStates.ACTIVE,
        });
        expect(trackIEEventSpy).toHaveBeenCalledWith({
          objectName: `${eventName} Banner`,
          actionName: 'Displayed',
          properties: {
            products: 'All',
            ...properties,
          },
        });
      },
    );

    test('should not track banner when type is rejected and banner message is missing ', () => {
      trackBannerDisplayed({
        type: BannerType.REJECTED,
        bannerMessage: null,
        pgProductState: ICProductStates.ACTIVE,
        ppliProductState: ICProductStates.ACTIVE,
      });
      expect(trackIEEventSpy).not.toHaveBeenCalled();
    });

    test("should not track banner when type doesn't match", () => {
      trackBannerDisplayed({
        type: '' as BannerType,
        bannerMessage: null,
        pgProductState: ICProductStates.ACTIVE,
        ppliProductState: ICProductStates.ACTIVE,
      });
      expect(trackIEEventSpy).not.toHaveBeenCalled();
    });
  });
});
