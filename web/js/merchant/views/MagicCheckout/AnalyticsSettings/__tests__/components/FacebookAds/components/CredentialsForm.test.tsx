import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import FacebookAdsCredentialsForm from 'merchant/views/MagicCheckout/AnalyticsSettings/components/FacebookAds/components/CredentialsForm';

const props = {
  setStep: jest.fn(),
  onSavingAccountCreds: jest.fn(),
  isSaving: false,
};

const INPUT_FIELDS = [
  {
    placeholderText: 'Enter Pixel ID',
    value: 'pixel123',
  },
  {
    placeholderText: 'Enter access token',
    value: 'test123',
  },
];
describe('testing FacebookAdsCredentialsForm component', () => {
  test('component should render properly', () => {
    render(<FacebookAdsCredentialsForm {...props} />);

    const integrateBackendCta = screen.getByRole('button', {
      name: 'Integrate backend',
    });

    expect(integrateBackendCta).toBeDisabled();
  });

  test.each(INPUT_FIELDS)('should be able to enter creds', async (field) => {
    render(<FacebookAdsCredentialsForm {...props} />);

    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to go click on back and save creds cta', async () => {
    render(<FacebookAdsCredentialsForm {...props} />);

    const backCta = screen.getByText('Back');
    await userEvent.click(backCta);
    expect(props.setStep).toBeCalled();

    const pixelId = screen.getByPlaceholderText(new RegExp('Enter Pixel Id', 'i'));
    const accessToken = screen.getByPlaceholderText(new RegExp('access token', 'i'));

    await userEvent.type(pixelId, 'pixel123');
    await userEvent.type(accessToken, 'test123');

    const integrateBackendCta = screen.getByRole('button', {
      name: 'Integrate backend',
    });

    expect(integrateBackendCta).not.toBeDisabled();
    await userEvent.click(integrateBackendCta);
    expect(props.onSavingAccountCreds).toBeCalled();
  });
});
