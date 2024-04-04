import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import ClickpostForm from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/components/ClickpostForm';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const INPUT_FIELDS = [
  {
    placeholderText: 'Enter username',
    value: 'test_user',
  },
  {
    placeholderText: 'Enter key',
    value: 'test_password',
  },
];

describe('testing Clickpost component', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  test('component should render properly', () => {
    render(<ClickpostForm />);

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to Clickpost',
    });

    expect(linkAccountCta).toBeDisabled();
  });

  test.each(INPUT_FIELDS)('should be able to enter creds', async (field) => {
    render(<ClickpostForm />);

    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to click on save creds cta', async () => {
    render(<ClickpostForm />);

    const usernameInput = screen.getByPlaceholderText(new RegExp('Enter username', 'i'));

    const passwordInput = screen.getByPlaceholderText(new RegExp('Enter key', 'i'));

    await userEvent.type(usernameInput, 'test_user');
    await userEvent.type(passwordInput, 'test_password');

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to Clickpost',
    });

    expect(linkAccountCta).not.toBeDisabled();
    await userEvent.click(linkAccountCta);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
