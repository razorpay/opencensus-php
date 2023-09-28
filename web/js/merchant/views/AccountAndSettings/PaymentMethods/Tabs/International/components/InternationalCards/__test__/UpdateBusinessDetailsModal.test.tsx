import UpdateBusinessDetailsModal from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/UpdateBusinessDetailsModal';
import * as modalActions from 'merchant_common/reducers/modals';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import 'jest-location-mock';

const closeModalSpy = jest.spyOn(modalActions, 'closeModal');

const renderApp = () =>
  render(<UpdateBusinessDetailsModal />, {
    renderViaRouteGuard: false,
  });

describe('UpdateBusinessDetailsModal', () => {
  test('should render UpdateBusinessDetailsModal content', () => {
    renderApp();
    expect(screen.getByText('Update website details')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your website must be registered to request for international payments on payment gateway',
      ),
    ).toBeInTheDocument();
    const updateBtn = screen.getByRole('button', { name: 'Update' });
    expect(updateBtn).toBeInTheDocument();

    const closeBtn = screen.getByRole('button', { name: 'Close' });
    expect(closeBtn).toBeInTheDocument();
  });

  test('should closeModal on clicking Close button', async () => {
    renderApp();
    const closeBtn = screen.getByRole('button', { name: 'Close' });
    await userEvent.click(closeBtn);
    expect(closeModalSpy).toHaveBeenCalled();
  });

  test('should redirect to business details on clicking update', async () => {
    const { history } = renderApp();
    const updateBtn = screen.getByRole('button', { name: 'Update' });
    await userEvent.click(updateBtn);
    expect(history.location.pathname).toBe('/website-app-settings/business-website-details');
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
