import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import track from 'merchant/views/PaymentButton/PaymentButton/List/track';
import { App, Paymentbuttons } from 'merchant/views/PaymentButton/__test__/mocks/fixtures/List';

jest.spyOn(track, 'createEnter').mockImplementation(() => {});
jest.spyOn(track, 'getCode').mockImplementation(() => {});

/**
 * @todo
 * 1. Test the following:
 *   - Items is being overwritten as blank [] in the test , even after passing the initialState as above. (will increase the coverage once fixed)
 */

describe('Payment Button List View', () => {
  const renderApp = () =>
    render(<App />, {
      initialState: {
        session: {
          user: {
            isAllowedEdit: () => true,
            isOrgAxis: () => false,
            isPaymentButtonEnabledByRazorX: true,
          },
          org: { custom_code: 'rzp' },
        },
        paymentbuttons: Paymentbuttons,
      },
    });

  test('Payment Button App component should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render Payment Button in PB List View', () => {
    renderApp();
    const paymentButton = screen.getByText('Payment Buttons');
    expect(paymentButton).toBeInTheDocument();
  });

  test('should render Subscription Button in List View', () => {
    renderApp();
    expect(screen.getByText('Subscription Buttons')).toBeInTheDocument();
  });

  test('should be clickable create "Payment Button" in List View', async () => {
    renderApp();
    const paymentButton = screen.getByText('Create Payment Button');
    expect(paymentButton).toBeInTheDocument();
    await userEvent.click(paymentButton);
    expect(track.createEnter).toHaveBeenCalled();
  });

  test('should be clickable "Need Help, Take tour" in List View', async () => {
    renderApp();
    const tourCTA = screen.getByText('Need help? Take a tour');
    expect(tourCTA).toBeInTheDocument();
    await userEvent.click(tourCTA);
    const initiateButton = screen.getByRole('button', {
      name: /Yes/i,
    });
    screen.debug();
    const cancelButton = screen.getByRole('button', {
      name: /No/i,
    });
    expect(initiateButton).toBeInTheDocument();
    expect(cancelButton).toBeInTheDocument();
    await userEvent.click(initiateButton);
    expect(
      screen.getByText('This tour will give you a quick guide on this product.'),
    ).toBeInTheDocument();
    expect(screen.getByText('Restart the Tour?')).toBeInTheDocument();
    const startTour = screen.getByRole('button', {
      name: /Yes/i,
    });
    expect(startTour).toBeInTheDocument();
  });

  test('should calls the search button & expect it to be in the document', () => {
    renderApp();
    const searchButton = screen.getByRole('button', {
      name: /Search/i,
    });
    expect(searchButton).toBeInTheDocument();
  });
});
