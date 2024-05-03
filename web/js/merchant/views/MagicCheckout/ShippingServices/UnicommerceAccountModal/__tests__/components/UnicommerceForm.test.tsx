import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import UnicommerceForm from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/components/UnicommerceForm';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const INPUT_FIELDS = [
  {
    placeholderText: 'Enter username',
    value: 'test_user',
  },
  {
    placeholderText: 'Enter password',
    value: 'test_password',
  },
  {
    placeholderText: 'Enter tenant',
    value: 'test_tenant',
  },
];

describe('testing Unicommerce component', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  test('component should render properly', () => {
    render(<UnicommerceForm />);

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to Unicommerce',
    });

    expect(linkAccountCta).toBeDisabled();
  });

  test.each(INPUT_FIELDS)('should be able to enter creds', async (field) => {
    render(<UnicommerceForm />);

    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to click on save creds cta', async () => {
    render(<UnicommerceForm />);

    const usernameInput = screen.getByPlaceholderText(new RegExp('Enter username', 'i'));

    const passwordInput = screen.getByPlaceholderText(new RegExp('Enter password', 'i'));

    const tenantInput = screen.getByPlaceholderText(new RegExp('Enter tenant', 'i'));

    await userEvent.type(usernameInput, 'test_user');
    await userEvent.type(passwordInput, 'test_password');
    await userEvent.type(tenantInput, 'test_tenant');

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to Unicommerce',
    });

    expect(linkAccountCta).not.toBeDisabled();
    await userEvent.click(linkAccountCta);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });

  test('should show error, if the username validation fails', async () => {
    render(<UnicommerceForm />);

    const usernameInput = screen.getByPlaceholderText(new RegExp('Enter username', 'i'));

    const passwordInput = screen.getByPlaceholderText(new RegExp('Enter password', 'i'));

    const tenantInput = screen.getByPlaceholderText(new RegExp('Enter tenant', 'i'));

    await userEvent.type(usernameInput, 'test_user.com');
    await userEvent.type(passwordInput, 'test_password');
    await userEvent.type(tenantInput, 'test_tenant');

    expect(screen.queryByText(/Please enter valid username/i)).toBeInTheDocument();

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to Unicommerce',
    });

    expect(linkAccountCta).toBeDisabled();
  });
});
