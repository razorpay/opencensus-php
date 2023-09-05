import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementOverview from 'merchant/views/Transactions/v1/Payments/components/SettlementOverview';
import { render, screen, userEvent } from 'test-utils';
import { analyticsTrack } from 'common/utils/analytics';

describe('SettlementOverview', () => {
  const payment = {
    transaction: {
      settlement: {
        id: 'settlement_id',
        utr: 'settlement_utr',
        settled_by: 'settled_by',
        provider: 'settlement_provider_id',
      },
    },
  };
  const defaultProps = {
    payment,
    user: {
      hideForNIASupportRole: true,
      isSingleReconEnabled: true,
      isOptimizerEnabled: true,
    },
    terminalProviders: [
      {
        Terminal_id: 'settlement_provider_id',
        Provider_name: 'settlement_provider',
      },
    ],
  };

  const renderApp = ({ props } = {}) => {
    return render(<SettlementOverview {...defaultProps} {...props} />);
  };

  test('should render settlement view', () => {
    renderApp();
    ['settlement_id', 'settlement_utr', 'settlement_provider'].forEach((settlementDetail) =>
      expect(screen.getByText(settlementDetail)).toBeInTheDocument(),
    );
  });

  test('should not render settlement provider when provider is not present', () => {
    renderApp({
      props: {
        payment: {
          transaction: {
            settlement: {
              ...payment.transaction.settlement,
              settled_by: 'razorpay',
            },
          },
        },
      },
    });
    expect(screen.queryByText('settlement_provider')).not.toBeInTheDocument();
  });

  test('should not call analytics event when settlement id link is clicked without page value', async () => {
    renderApp();
    await userEvent.click(screen.getByText('settlement_id'));
    expect(analyticsTrack).not.toHaveBeenCalled();
  });

  test('should call analytics event when settlement id link is clicked with page value', async () => {
    renderApp({
      props: {
        page: 'settlement',
      },
    });
    await userEvent.click(screen.getByText('settlement_id'));
    expect(analyticsTrack).toHaveBeenCalledWith({
      actionName: 'click',
      objectName: 'settlement id',
      properties: {
        activation_status: undefined,
        business_type: undefined,
        current_activation_status: undefined,
        merchantId: undefined,
        mode: null,
        pageUrl: 'http://localhost/',
        previous_activation_status: undefined,
        settlement_id: 'settlement_id',
        slug: '/',
        userId: undefined,
        userRole: undefined,
        user_business_category: undefined,
        user_business_sub_category: undefined,
      },
      screen: 'settlement',
      toLumberjack: true,
    });
  });
});
