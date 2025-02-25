import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import EnableInstantRefundsModal from 'merchant/views/Transactions/v1/Payments/components/EnableInstantRefundsModal';
import { fireEvent, render, screen, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { analyticsTrack } from 'common/utils/analytics';
import { rest } from 'msw';

import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

describe('EnableInstantRefundsModal', () => {
  const defaultProps = {
    pricing: {
      rules: [
        {
          fixed_rate: 100,
          amount_range_min: 100,
          amount_range_max: 10000,
        },
        {
          fixed_rate: 50,
          amount_range_min: 10,
          amount_range_max: 1000,
        },
      ],
    },
  };

  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <EnableInstantRefundsModal {...defaultProps} {...rest} />
      </Provider>
    );
  };

  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: () => ({
        initiated: jest.fn(),
      }),
    };
  });

  describe('Normal refund', () => {
    test('should render normal refund when speed is normal', () => {
      render(<App speed="normal" />);
      const enableNormalRefund = screen.getAllByText('Enable Normal Refund');
      expect(enableNormalRefund.length).toBe(2);
      expect(enableNormalRefund[0]).toBeInTheDocument();
    });

    test('should call success analyticsTrack when Enable Normal Refund button is clicked', async () => {
      render(<App speed="normal" updated={jest.fn()} />);
      const enableNormalRefundButton = screen.getAllByText('Enable Normal Refund')[1];
      fireEvent.click(enableNormalRefundButton);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          actionName: 'result',
          objectName: 'normal refund',
          properties: {
            location: 'configuration',
            status: 'Success',
          },
          screen: 'settings',
        });
      });
    });

    test('should call failure analyticsTrack when Enable Normal Refund button is clicked', async () => {
      server.use(
        rest.put('*/merchant/api/test/account/config', (req, res, ctx) => {
          return res(ctx.errors([{ message: 'Some error occurred' }]), ctx.delay(50));
        }),
      );
      render(<App speed="normal" updated={jest.fn()} />);
      const enableNormalRefundButton = screen.getAllByText('Enable Normal Refund')[1];
      fireEvent.click(enableNormalRefundButton);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          actionName: 'result',
          objectName: 'normal refund',
          properties: {
            location: 'configuration',
            status: 'Failure',
            failureReason: 'Network Error',
          },
          screen: 'settings',
        });
      });
    });
  });

  describe('Instant refund', () => {
    test('should render instant refund when speed is not normal', () => {
      render(<App speed="instant" />);
      const enableInstantRefund = screen.getAllByText('Enable Instant Refund');
      expect(enableInstantRefund.length).toBe(2);
      expect(enableInstantRefund[0]).toBeInTheDocument();
    });

    test('should call success analyticsTrack when Enable Instant Refund button is clicked', async () => {
      render(<App speed="instant" updated={jest.fn()} />);
      const enableInstantRefundButton = screen.getAllByText('Enable Instant Refund')[1];
      fireEvent.click(enableInstantRefundButton);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          actionName: 'result',
          objectName: 'instant refund',
          properties: {
            location: 'configuration',
            status: 'Success',
          },
          screen: 'settings',
        });
      });
    });

    test('should call success analyticsTrack when Enable Instant Refund button is clicked & custom_pricing is true', async () => {
      render(
        <App
          speed="instant"
          updated={jest.fn()}
          pricing={{
            custom_pricing: true,
          }}
        />,
      );
      const enableInstantRefundButton = screen.getAllByText('Enable Instant Refund')[1];
      fireEvent.click(enableInstantRefundButton);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          actionName: 'result',
          objectName: 'instant refund',
          properties: {
            location: 'configuration',
            status: 'Success',
          },
          screen: 'settings',
        });
      });
    });

    test('should call failure analyticsTrack when Enable Instant Refund button is clicked', async () => {
      server.use(
        rest.put('*/merchant/api/test/account/config', (req, res, ctx) => {
          return res(ctx.errors([{ message: 'Some error occurred' }]), ctx.delay(50));
        }),
      );
      render(<App speed="instant" updated={jest.fn()} />);
      const enableInstantRefundButton = screen.getAllByText('Enable Instant Refund')[1];
      fireEvent.click(enableInstantRefundButton);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          actionName: 'result',
          objectName: 'instant refund',
          properties: {
            location: 'configuration',
            status: 'Failure',
            failureReason: 'Network Error',
          },
          screen: 'settings',
        });
      });
    });
  });

  describe('onCloseClick', () => {
    test('should call analyticsTrack when speed is normal', () => {
      render(<App speed="normal" />);
      const closeButton = screen.getByTestId('modal-header-close-btn');
      fireEvent.click(closeButton);
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'enable normal refund popup',
        properties: { actionName: 'close', location: 'configuration' },
        screen: 'settings',
      });
    });

    test('should call analyticsTrack when speed is instant', () => {
      render(<App speed="instant" />);
      const closeButton = screen.getByTestId('modal-header-close-btn');
      fireEvent.click(closeButton);
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'enable instant refund popup',
        properties: { actionName: 'close', location: 'configuration' },
        screen: 'settings',
      });
    });

    test('should call analyticsTrack when speed is instant & custom_pricing is true', () => {
      render(
        <App
          speed="instant"
          pricing={{
            custom_pricing: true,
          }}
        />,
      );
      const closeButton = screen.getByTestId('modal-header-close-btn');
      fireEvent.click(closeButton);
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'enable instant refund popup',
        properties: { actionName: 'close', location: 'configuration' },
        screen: 'settings',
      });
    });
  });

  describe('Pricing when speed is instant', () => {
    test('should render Hide Pricing when Show Pricing button is clicked', async () => {
      render(<App speed="instant" />);
      const showPricing = screen.getByText('Show Pricing');
      fireEvent.click(showPricing);
      await waitFor(() => {
        expect(screen.getByText('Hide Pricing')).toBeInTheDocument();
      });
    });

    test('should render Show Pricing when Hide Pricing button is clicked', async () => {
      render(<App speed="instant" />);
      const showPricing = screen.getByText('Show Pricing');
      fireEvent.click(showPricing);
      await waitFor(() => {
        expect(screen.getByText('Hide Pricing')).toBeInTheDocument();
      });
      const hidePricing = screen.getByText('Hide Pricing');
      fireEvent.click(hidePricing);
      await waitFor(() => {
        expect(showPricing).toBeInTheDocument();
      });
    });

    describe('Custom pricing', () => {
      test('should render Custom Pricing when custom_pricing is true', async () => {
        render(
          <App
            speed="instant"
            pricing={{
              custom_pricing: true,
            }}
          />,
        );
        const showPricing = screen.getByText('Show Pricing');
        fireEvent.click(showPricing);
        await waitFor(() => {
          expect(screen.getByText('To know your pricing, please')).toBeInTheDocument();
          expect(screen.getByText('contact support')).toBeInTheDocument();
        });
      });

      test('should raiseTicket when contact support is clicked', async () => {
        render(
          <App
            speed="instant"
            pricing={{
              custom_pricing: true,
            }}
          />,
        );
        const showPricing = screen.getByText('Show Pricing');
        fireEvent.click(showPricing);
        await waitFor(() => {
          expect(screen.getByText('contact support')).toBeInTheDocument();
        });
        fireEvent.click(screen.getByText('contact support'));
        expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
      });
    });
  });

  describe('Click here to know more', () => {
    test('should call rzpAnalytics when speed is normal', () => {
      render(<App speed="normal" />);
      const clickHere = screen.getByText('click here');
      fireEvent.click(clickHere);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventAction: 'Enable Normal Refund',
        eventCategory: 'Dashboard - Instant Refund',
        eventLabel: 'Learn More | Enable Normal Refund',
      });
    });

    test('should call analyticsTrack when speed is instant', () => {
      render(<App speed="instant" />);
      const clickHere = screen.getByText('click here');
      fireEvent.click(clickHere);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventAction: 'Enable Instant Refund',
        eventCategory: 'Dashboard - Instant Refund',
        eventLabel: 'Learn More | Enable Instant Refund',
      });
    });

    test('should call analyticsTrack when speed is instant & custom_pricing is true', () => {
      render(
        <App
          speed="instant"
          pricing={{
            custom_pricing: true,
          }}
        />,
      );
      const clickHere = screen.getByText('click here');
      fireEvent.click(clickHere);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventAction: 'Enable Instant Refund',
        eventCategory: 'Dashboard - Instant Refund',
        eventLabel: 'Learn More | Enable Instant Refund',
      });
    });
  });
});
