import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import {
  defaultProps,
  App,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentDetails';
import { analyticsTrack } from 'common/utils/analytics';
import { createMemoryHistory } from 'history';

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
      render(<App />, { history });
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
    test('should render settlement details when isUxRevampPhase2Enabled is true', () => {
      render(<App />);
      expect(screen.getByText('Settlement Details')).toBeInTheDocument();
    });

    test('should call trackKnowMore when clicked on settlement know more & when isUxRevampPhase2Enabled is true', () => {
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

    test('should call trackSameDaySettlement when clicked on same day settlement & when isUxRevampPhase2Enabled is true', () => {
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

    test('should call trackSettlementClose when clicked on settlement close & when isUxRevampPhase2Enabled is true', () => {
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

    test('should call trackSettlementOverView when clicked on settlement info & when isUxRevampPhase2Enabled is true', () => {
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

    test('should call handleSettlementGuideClick when clicked on settlement guide info & when isUxRevampPhase2Enabled is true', () => {
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

    test('should call trackContactSupport when clicked on settlement contact support & when isUxRevampPhase2Enabled is true', () => {
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

    test('should render settlement details when isUxRevampPhase2Enabled is false', () => {
      render(<App user={{ ...defaultProps.user, isUxRevampPhase2Enabled: false }} />);
      expect(screen.getByText('Settlement Details')).toBeInTheDocument();
    });

    test('should render settled settlement details when isUxRevampPhase2Enabled is false', () => {
      render(
        <App
          user={{ ...defaultProps.user, isUxRevampPhase2Enabled: false }}
          payment={{
            ...defaultProps.payment,
            transaction: {
              settlement: {},
              settled_at: '10-10-2022',
            },
          }}
        />,
      );
      expect(screen.getByText('Settlement Details')).toBeInTheDocument();
      expect(screen.getByText(/Settled on/)).toBeInTheDocument();
    });

    test('should render to be settled on settlement details when isUxRevampPhase2Enabled is false', () => {
      render(
        <App
          user={{ ...defaultProps.user, isUxRevampPhase2Enabled: false }}
          payment={{
            ...defaultProps.payment,
            transaction: {
              settled_at: '10-10-2022',
            },
          }}
        />,
      );
      expect(screen.getByText('Settlement Details')).toBeInTheDocument();
      expect(screen.getByText(/To be settled on/)).toBeInTheDocument();
    });
  });
});
