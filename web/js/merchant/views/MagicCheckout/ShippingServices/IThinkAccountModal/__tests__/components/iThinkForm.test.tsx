import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import IThinkForm from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/components/IThinkForm';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const INPUT_FIELDS = [
  {
    placeholderText: 'API key of your iThink Logistics account',
    value: 'test_key',
  },
  {
    placeholderText: 'Secret key of your iThink Logistics account',
    value: 'test_secret',
  },
];

describe('testing IThinkForm component', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  test('component should render properly', () => {
    render(<IThinkForm />);

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to iThink Logistics',
    });

    expect(linkAccountCta).toBeDisabled();
  });

  test.each(INPUT_FIELDS)('should be able to enter creds', async (field) => {
    render(<IThinkForm />);

    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to click on save creds cta', async () => {
    render(<IThinkForm />);

    const apiKeyInput = screen.getByPlaceholderText(
      new RegExp('API key of your iThink Logistics account', 'i'),
    );
    const apiSecretInput = screen.getByPlaceholderText(
      new RegExp('Secret key of your iThink Logistics account', 'i'),
    );

    await userEvent.type(apiKeyInput, 'test_key');
    await userEvent.type(apiSecretInput, 'test_secret');

    const linkAccountCta = screen.getByRole('button', {
      name: 'Connect to iThink Logistics',
    });

    expect(linkAccountCta).not.toBeDisabled();
    await userEvent.click(linkAccountCta);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});
