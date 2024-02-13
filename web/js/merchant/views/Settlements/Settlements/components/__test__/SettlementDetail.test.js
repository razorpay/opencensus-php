import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { getFormattedAmountByParts } from 'common/utils/rzp-utils';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import {
  props as defaultProps,
  activatedUser,
  inActivatedUser,
  unActivatedUserOnHold,
  inActivatedUserOnHoldAndUnderReview,
} from 'merchant/views/Settlements/Settlements/components/__test__/mocks/fixtures/SettlementDetail';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent } from 'test-utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

describe('SettlementDetails.js', () => {
  const renderApp = (user, transactionOnHold = false) => {
    render(
      <SettlementDetail {...defaultProps} user={user} transactionOnHold={transactionOnHold} />,
      {
        showModal: true,
      },
    );
  };

  const closeModalsSpy = jest.spyOn(ModalActions, 'closeModal');
  const handleSettlementGuideClickSpy = jest.spyOn(defaultProps, 'handleSettlementGuideClick');

  beforeAll(() => {
    window.rzpTicketSystem = true;
  });

  beforeEach(() => {
    closeModalsSpy.mockClear();
    handleSettlementGuideClickSpy.mockClear();
  });

  afterAll(() => {
    window.rzpTicketSystem = false;
  });

  describe('Modal header', () => {
    test('should render modal header', () => {
      renderApp(activatedUser);
      const header = screen.queryByText('Settlement Details');
      expect(header).toBeInTheDocument();
    });

    test('should render modal close CTA', () => {
      renderApp(activatedUser);
      const closeBtn = screen.queryByTestId('modal-header-close-btn');
      expect(closeBtn).toBeInTheDocument();
    });

    test('should call close modal on CTA click', async () => {
      renderApp(activatedUser);
      const closeBtn = screen.queryByTestId('modal-header-close-btn');
      await userEvent.click(closeBtn);
      expect(closeModalsSpy).toBeCalled();
    });
  });

  describe('Settlements are enabled', () => {
    test('should render settlement amount', () => {
      renderApp(activatedUser);
      const formattedSettlementAmount = getFormattedAmountByParts(33684700);
      const settlementAmount = screen.queryByText(formattedSettlementAmount?.integer);
      expect(settlementAmount).toBeInTheDocument();
    });

    test('should render CTA', () => {
      renderApp(activatedUser);
      const settlementGuideCTA = screen.queryByRole('button', { name: 'Settlement Guide' });
      expect(settlementGuideCTA).toBeInTheDocument();
    });

    test('should open settlement guide on CTA click', async () => {
      renderApp(activatedUser);
      const settlementGuideCTA = screen.queryByRole('button', { name: 'Settlement Guide' });
      expect(settlementGuideCTA).toBeInTheDocument();
      await userEvent.click(settlementGuideCTA);
      expect(handleSettlementGuideClickSpy).toBeCalled();
    });

    test('should render meta data', () => {
      renderApp(activatedUser);
      const settlementInfo = screen.queryByText(
        'The actual time taken for the settled amount to reflect in your bank account depends on the bank’s processing time.',
      );
      expect(settlementInfo).toBeInTheDocument();
      const settlementMeta = screen.queryByText(
        'This is an estimate of the settlement amount and the actual settled amount may vary based on the latest transactions in your account.',
      );
      expect(settlementMeta).toBeInTheDocument();
    });
  });

  describe('Settlements on hold', () => {
    test('should render meta for on hold', () => {
      renderApp(unActivatedUserOnHold, true);
      const settlementInfo = screen.queryByText('Settlements under review');
      const settlementMeta = screen.queryByText(
        `Your settlements are currently under review and not getting processed.`,
      );

      expect(settlementInfo).toBeInTheDocument();
      expect(settlementMeta).toBeInTheDocument();
    });

    test('should render CTAs', () => {
      renderApp(unActivatedUserOnHold, true);
      const contactSupportBtn = screen.queryByRole('button', { name: 'Contact Support' });
      const settlementGuideBtn = screen.queryByRole('button', { name: 'Settlement Guide' });

      expect(contactSupportBtn).toBeInTheDocument();
      expect(settlementGuideBtn).toBeInTheDocument();
    });

    test('should call close modal on KYC CTA click', async () => {
      renderApp(unActivatedUserOnHold, true);
      const contactSupportBtn = screen.queryByRole('button', { name: 'Contact Support' });

      expect(contactSupportBtn).toBeInTheDocument();
      await userEvent.click(contactSupportBtn);
      expect(closeModalsSpy).toBeCalled();
    });
  });

  describe('Unactivated user', () => {
    test('should render CTAs', () => {
      renderApp(inActivatedUser, true);
      const kycDetailBtn = screen.queryByRole('button', { name: 'KYC Process Details' });
      const completeKYCBtn = screen.queryByRole('button', { name: 'Complete KYC' });

      expect(kycDetailBtn).toBeInTheDocument();
      expect(completeKYCBtn).toBeInTheDocument();
    });

    test('should render meta for an unactivated user', () => {
      renderApp(inActivatedUser, true);
      const settlementInfo = screen.queryByText(
        'Your Settlements will be processed post KYC submission',
      );
      const settlementMeta = screen.queryByText(
        `Complete KYC to enable settlements for your account.`,
      );

      expect(settlementInfo).toBeInTheDocument();
      expect(settlementMeta).toBeInTheDocument();
    });

    test('should call close modal on KYC CTA click', async () => {
      renderApp(inActivatedUser, true);
      const completeKYCBtn = screen.queryByRole('button', { name: 'Complete KYC' });

      expect(completeKYCBtn).toBeInTheDocument();
      await userEvent.click(completeKYCBtn);
      expect(closeModalsSpy).toBeCalled();
    });
  });

  describe('Settlements on hold & activation status under review', () => {
    test('should render meta for on hold', () => {
      renderApp(inActivatedUserOnHoldAndUnderReview, true);
      const settlementInfo = screen.queryAllByText(/Settlements under review/)[0];
      const settlementMeta = screen.queryByText(`We are reviewing your documents.`);

      expect(settlementInfo).toBeInTheDocument();
      expect(settlementMeta).toBeInTheDocument();
    });

    test('should render CTAs', () => {
      renderApp(inActivatedUserOnHoldAndUnderReview, true);
      const kycProcessDetailsBtn = screen.queryByRole('button', { name: 'KYC Process Details' });
      const completeKYCBtn = screen.queryByRole('button', { name: 'Complete KYC' });

      expect(kycProcessDetailsBtn).toBeInTheDocument();
      expect(completeKYCBtn).toBeInTheDocument();
    });

    test('should call close modal on KYC CTA click', async () => {
      renderApp(inActivatedUserOnHoldAndUnderReview, true);
      const completeKYCBtn = screen.queryByRole('button', { name: 'Complete KYC' });

      expect(completeKYCBtn).toBeInTheDocument();
      await userEvent.click(completeKYCBtn);
      expect(closeModalsSpy).toBeCalled();
    });
  });
});
