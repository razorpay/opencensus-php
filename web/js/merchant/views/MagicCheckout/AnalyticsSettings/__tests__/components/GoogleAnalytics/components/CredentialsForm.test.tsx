import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import GoogleAnalyticsCredentialsForm from 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAnalytics/components/CredentialsForm';

const props = {
  setStep: jest.fn(),
  onSavingAccountCreds: jest.fn(),
  isSaving: false,
};

const INPUT_FIELDS = [
  {
    placeholderText: 'Enter measurement ID',
    value: 'pixel123',
  },
  {
    placeholderText: 'Enter api secret',
    value: 'test123',
  },
];
describe('testing GoogleAnalyticsCredentialsForm component', () => {
  test('component should render properly', () => {
    render(<GoogleAnalyticsCredentialsForm {...props} />);

    const integrateBackendCta = screen.getByRole('button', {
      name: 'Integrate backend',
    });

    expect(integrateBackendCta).toBeDisabled();
  });

  test.each(INPUT_FIELDS)('should be able to enter creds', async (field) => {
    render(<GoogleAnalyticsCredentialsForm {...props} />);

    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to go click on back and save creds cta', async () => {
    render(<GoogleAnalyticsCredentialsForm {...props} />);

    const backCta = screen.getByText('Back');
    await userEvent.click(backCta);
    expect(props.setStep).toBeCalled();

    const measurementId = screen.getByPlaceholderText(new RegExp('Enter measurement Id', 'i'));
    const apiSecret = screen.getByPlaceholderText(new RegExp('Enter api secret', 'i'));

    await userEvent.type(measurementId, 'measurement123');
    await userEvent.type(apiSecret, 'test123');

    const integrateBackendCta = screen.getByRole('button', {
      name: 'Integrate backend',
    });

    expect(integrateBackendCta).not.toBeDisabled();
    await userEvent.click(integrateBackendCta);
    expect(props.onSavingAccountCreds).toBeCalled();
  });
});
