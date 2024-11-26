import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createMemoryHistory } from 'history';

import { analyticsTrack } from 'common/utils/analytics';
import { App } from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentDetails';
import { render, screen, fireEvent } from 'test-utils';

export const queryClient = new QueryClient();

describe('PaymentDetails', () => {
  describe('Capture payment', () => {
    test('should call capturePayment & onActionSideBar when clicked on capture payment on transactions screen', () => {
      render(<App />);
      fireEvent.click(screen.getByText('Capture Payment'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'capture payment',
        properties: {
          location: 'payments',
        },
        screen: 'transactions',
        toLumberjack: true,
      });

      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'action items on sidebar',
        properties: {
          location: 'payments',
        },
        screen: 'transactions',
        toLumberjack: true,
      });
    });

    test('should call capturePayment & onActionSideBar when clicked on capture payment on home page screen', () => {
      const history = createMemoryHistory();
      const state = { fromHomePage: true };
      history.push('/', state);

      render(
        <QueryClientProvider client={queryClient}>
          <App />
        </QueryClientProvider>,
        { history },
      );

      fireEvent.click(screen.getByText('Capture Payment'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'capture payment',
        properties: {
          location: 'payments',
        },
        screen: 'home page',
        toLumberjack: true,
      });
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'action items on sidebar',
        properties: {
          location: 'payments',
        },
        screen: 'home page',
        toLumberjack: true,
      });
    });
  });

  describe('Settlement details', () => {
    test('should render settlement details', () => {
      render(<App />);
      expect(screen.getByText('Settlement Details')).toBeInTheDocument();
    });

    test('should call trackKnowMore when clicked on settlement know more', () => {
      render(<App />);
      fireEvent.click(screen.getByText('Know More'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments detail know more',
        properties: {
          location: 'payments',
        },
        screen: 'payments',
        toLumberjack: true,
      });
    });

    test('should call trackSameDaySettlement when clicked on same day settlement', () => {
      render(<App />);
      fireEvent.click(screen.getByText('Track Same Day Settlement'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments detail settlement enabled',
        properties: {
          location: 'payments',
        },
        screen: 'payments',
        toLumberjack: true,
      });
    });

    test('should call trackSettlementClose when clicked on settlement close', () => {
      render(<App />);
      fireEvent.click(screen.getByText('Track Settlement Close'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments detail popup closed',
        properties: {
          location: 'payments',
        },
        screen: 'payments',
        toLumberjack: true,
      });
    });

    test('should call trackSettlementOverView when clicked on settlement info', () => {
      render(<App />);
      fireEvent.click(screen.getByText('SettlementInfo'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments settlement viewed',
        properties: {
          location: 'payments',
        },
        screen: 'payments Details',
        toLumberjack: true,
      });
    });

    test('should call handleSettlementGuideClick when clicked on settlement guide info', () => {
      render(<App />);
      fireEvent.click(screen.getByText('Settlement Guide'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments detail guide',
        properties: {
          location: 'payments',
        },
        screen: 'payments',
        toLumberjack: true,
      });
    });

    test('should call trackContactSupport when clicked on settlement contact support', () => {
      render(<App />);
      fireEvent.click(screen.getByText('Contact Support'));
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'payments detail support',
        properties: {
          location: 'payments',
        },
        screen: 'payments',
        toLumberjack: true,
      });
    });
  });
});
