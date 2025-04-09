import React from 'react';

import { useMobile } from 'common/hooks/useMobile';
import { titleCase } from 'common/utils/rzp-utils';
import { TEXT_CONTENT } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';
import { MerchantOverview } from 'merchant/containers/Home/RTUX/MerchantOverview/index';
import { SettlementStatusBadge } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { getGreetingAndDate } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { getMockDataForHeroCardWithWidget, heroCardVariants } from './mocks/fixtures';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

const openModalSpy = jest.spyOn(ModalActions, 'openModal');
jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(),
}));

window.open = jest.fn();

const mockRetry = jest.fn();
jest.mock('merchant/widgets/hooks', () => ({
  useRetryWidget: () => [false, mockRetry],
}));

const heroCardsVariantsData = getMockDataForHeroCardWithWidget();
const renderApp = ({
  props = {},
  data = heroCardsVariantsData.SETTLEMENT_TODAY_CREATED,
  user = {},
} = {}) => {
  return render(<MerchantOverview data={data.data} {...props} queryKey={[]} />, {
    initialState: {
      session: {
        user: {
          ...user,
        },
      },
    },
  });
};

const runMerchantOverviewTestSuite = ({ isMobile }) => {
  describe(`RTUX MerchantOverview - ${isMobile ? 'mobile' : 'desktop'}`, () => {
    beforeEach(() => {
      (useMobile as jest.Mock).mockReturnValue(isMobile);
    });

    test('should show greeting msg and date', () => {
      const userName = 'Test Merchant';
      const { greeting, formattedDate } = getGreetingAndDate();
      renderApp({
        user: {
          name: userName,
        },
      });
      expect(
        screen.getByRole('heading', {
          name: `${greeting}, ${userName}!`,
        }),
      ).toBeVisible();
      expect(screen.getByText(formattedDate)).toBeVisible();
      expect(screen.getByText('Current balance')).toBeVisible();
    });

    test('should show non settlement shimmer while loading and user is not transacted', () => {
      renderApp({
        props: {
          isLoading: true,
        },
      });
      expect(screen.getByTestId('non-settlement-shimmer')).toBeVisible();
    });

    test('should show settlement shimmer while loading and user is transacted', () => {
      renderApp({
        props: {
          isLoading: true,
        },
        user: {
          isTransacted: true,
        },
      });
      expect(screen.getByTestId('settlement-shimmer')).toBeVisible();
    });

    test('should show error state when there is error and should retry API on try again click', async () => {
      renderApp({
        props: {
          error: 'Something went wrong',
        },
      });

      expect(screen.getByText(TEXT_CONTENT.ERROR_STATE_TITLE)).toBeVisible();
      const retryBtn = screen.getByRole('button', {
        name: /Try Again/i,
      });
      expect(retryBtn).toBeVisible();
      await userEvent.click(retryBtn);
      await waitFor(() => {
        expect(mockRetry).toHaveBeenCalled();
      });
    });

    test(`should show ${heroCardVariants.NON_SETTLEMENT_NOT_TRANSACTED} case`, async () => {
      const { history } = renderApp({
        data: heroCardsVariantsData.NON_SETTLEMENT_NOT_TRANSACTED,
      });

      expect(
        screen.getByRole('heading', {
          name: TEXT_CONTENT.NO_SETTLEMENT_NOT_TRANSACTED,
        }),
      ).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.NO_SETTLEMENT_NOT_TRANSACTED_SUBHEADING)).toBeVisible();
      const collectPaymentsBtn = await screen.findByRole('button', {
        name: 'Collect payments',
      });
      await userEvent.click(collectPaymentsBtn);
      await waitFor(() => {
        expect(history.location.pathname).toBe('/paymentlinks');
      });
    });

    test(`should show ${heroCardVariants.NON_SETTLEMENT_TRANSACTED} case`, async () => {
      const cardData = heroCardsVariantsData.NON_SETTLEMENT_TRANSACTED;
      renderApp({
        data: cardData,
      });
      expect(
        screen.getByRole('heading', {
          name: TEXT_CONTENT.NO_SETTLEMENT_TRANSACTED,
        }),
      ).toBeVisible();
      expect(
        screen.getByText(
          `We're on-track to deposit the payments in your bank account by ${cardData.data.hero_card_data.settlement_schedule}, as per your settlement cycle`,
        ),
      ).toBeVisible();
      const settlementGuideBtn = screen.getByRole('button', {
        name: 'View settlement guide',
      });
      expect(settlementGuideBtn).toBeVisible();
      await userEvent.click(settlementGuideBtn);
      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith(
          'https://razorpay.com/settlement',
          '_blank',
          'rel=noopener noreferrer',
        );
      });

      const settlementCycleBtn = screen.getByRole('button', {
        name: 'View settlement cycle',
      });
      expect(settlementCycleBtn).toBeVisible();
      await userEvent.click(settlementCycleBtn);
      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalled();
      });
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_CREATED} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_CREATED,
      });

      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.on_track))).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.TO_BE_PROCESSED_BY_8PM)).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_PROCESSED_BEFORE_SLA_PREVIOUS} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_PROCESSED_BEFORE_SLA_PREVIOUS,
      });
      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.processed))).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.TO_BE_PROCESSED_BY_8PM)).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_PROCESSED_AFTER_SLA_PREVIOUS} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_PROCESSED_AFTER_SLA_PREVIOUS,
      });
      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.processed))).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.PROCESSED_DEPOSITED)).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_PREVIOUS_ONLY}`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_DLEAYED_PREVIOUS,
      });
      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.delayed))).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.TO_BE_PROCESSED_BY_8PM)).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_FAIL_RETRY_SLA_NOT_BREACHED_PREVIOUS}`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_FAIL_RETRY_SLA_NOT_BREACHED_PREVIOUS,
      });
      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.FAIL_SLA_NOT_BREACHED)).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.FAIL_SLA_NOT_BREACHED)).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_FAIL_RETRY_SLA_BREACHED_PREVIOUS}`, async () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_FAIL_RETRY_SLA_BREACHED_PREVIOUS,
      });
      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.failed))).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.FAIL_SLA_BREACHED)).toBeVisible();
      const contactSupportBtn = screen.getByRole('button', {
        name: 'Contact Support',
      });
      expect(contactSupportBtn).toBeVisible();
      await userEvent.click(contactSupportBtn);
      await waitFor(() => {
        expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
      });
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_FAIL_SOH_PREVIOUS}`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_TODAY_FAIL_SOH_PREVIOUS,
      });
      expect(screen.getByText("Today's settlement")).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.failed))).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.SOH_UPDATE_BANK_ACCOUNT)).toBeVisible();
      expect(
        screen.getByRole('button', {
          name: 'Update Bank details',
        }),
      ).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_TODAY_MULTIPLE}`, () => {
      const cardData = heroCardsVariantsData.SETTLEMENT_TODAY_MULTIPLE;
      renderApp({
        data: cardData,
      });
      expect(
        screen.getByText(
          `Today, ${cardData.data?.hero_card_data?.settlement?.today?.total_count} settlements worth`,
        ),
      ).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_PREVIOUS_ONLY}`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_PREVIOUS_ONLY,
      });
      expect(screen.getByText(`Last settlement`)).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.processed))).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_BLOCK_FOH}`, async () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_BLOCK_FOH,
      });
      expect(screen.getByText(`Upcoming settlements are`)).toBeVisible();
      expect(screen.getByText(SettlementStatusBadge.blocked)).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.FOH_UPCOMMING_BLOCK)).toBeVisible();
      const contactSupportBtn = screen.getByRole('button', {
        name: 'Contact Support',
      });
      expect(contactSupportBtn).toBeVisible();
      await userEvent.click(contactSupportBtn);
      await waitFor(() => {
        expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
      });
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_BLOCK_MOH} case`, async () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_BLOCK_MOH,
      });
      expect(screen.getByText(`Upcoming settlements are`)).toBeVisible();
      expect(screen.getByText(SettlementStatusBadge.blocked)).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.MOH_UPCOMMING_BLOCK)).toBeVisible();
      const contactSupportBtn = screen.getByRole('button', {
        name: 'Contact Support',
      });
      expect(contactSupportBtn).toBeVisible();
      await userEvent.click(contactSupportBtn);
      await waitFor(() => {
        expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
      });
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_BLOCK_SOH} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_BLOCK_SOH,
      });

      expect(screen.getByText(`Upcoming settlements are`)).toBeVisible();
      expect(screen.getByText(SettlementStatusBadge.blocked)).toBeVisible();
      expect(screen.getByText(TEXT_CONTENT.SOH_UPCOMMING_BLOCK)).toBeVisible();
      expect(
        screen.getByRole('button', {
          name: 'Update Bank details',
        }),
      ).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_PAUSED_NO_NEXT_SETTLEMENT} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_PAUSED_NO_NEXT_SETTLEMENT,
      });
      expect(screen.getByText(`Last settlement`)).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.processed))).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_SKIP_NEGATIVE_BALANCE} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_SKIP_NEGATIVE_BALANCE,
      });
      expect(screen.getByText(`Upcoming settlement`)).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.skipped))).toBeVisible();
      expect(
        screen.getByText(TEXT_CONTENT.UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE),
      ).toBeVisible();
      expect(
        screen.getByRole('link', {
          name: 'Add Funds',
        }),
      ).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_SKIP_LESS_THAN_1} case`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_SKIP_LESS_THAN_1,
      });
      expect(screen.getByText(`Upcoming settlement`)).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.skipped))).toBeVisible();
      expect(
        screen.getByText('Settlement amount must be more than ₹1 to be settled'),
      ).toBeVisible();
    });

    test(`should show ${heroCardVariants.SETTLEMENT_UPCOMMING_SETTLEMENT_ON_TRACK}`, () => {
      renderApp({
        data: heroCardsVariantsData.SETTLEMENT_UPCOMMING_SETTLEMENT_ON_TRACK,
      });
      expect(screen.getByText(`Upcoming settlement`)).toBeVisible();
      expect(screen.getByText(titleCase(SettlementStatusBadge.on_track))).toBeVisible();
    });
  });
};

runMerchantOverviewTestSuite({
  isMobile: false,
});
runMerchantOverviewTestSuite({
  isMobile: true,
});
