import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import GoogleAdsCredentialsForm from 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAds/components/CredentialsForm';

const props = {
  setStep: jest.fn(),
  onSavingAccountCreds: jest.fn(),
  isSaving: false,
};

const INPUT_FIELDS = [
  {
    placeholderText: 'Enter conversion ID',
    value: 'pixel123',
  },
  {
    placeholderText: 'Enter conversion lable',
    value: 'test123',
  },
  {
    placeholderText: 'Enter adwords account number',
    value: 'account123',
  },
];
describe('testing GoogleAdsCredentialsForm component', () => {
  test('component should render properly', () => {
    render(<GoogleAdsCredentialsForm {...props} />);

    const integrateBackendCta = screen.getByRole('button', {
      name: 'Integrate backend',
    });

    expect(integrateBackendCta).toBeDisabled();
  });

  test.each(INPUT_FIELDS)('should be able to enter creds', async (field) => {
    render(<GoogleAdsCredentialsForm {...props} />);

    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to go click on back and save creds cta', async () => {
    render(<GoogleAdsCredentialsForm {...props} />);

    const backCta = screen.getByText('Back');
    await userEvent.click(backCta);
    expect(props.setStep).toBeCalled();

    const conversionId = screen.getByPlaceholderText(new RegExp('Enter conversion Id', 'i'));
    const conversionLable = screen.getByPlaceholderText(new RegExp('Enter conversion lable', 'i'));
    const adwordAccountNumber = screen.getByPlaceholderText(
      new RegExp('Enter adwords account number', 'i'),
    );

    await userEvent.type(conversionId, 'conversion123');
    await userEvent.type(conversionLable, 'test123');
    await userEvent.type(adwordAccountNumber, 'acount123');

    const integrateBackendCta = screen.getByRole('button', {
      name: 'Integrate backend',
    });

    expect(integrateBackendCta).not.toBeDisabled();
    await userEvent.click(integrateBackendCta);
    expect(props.onSavingAccountCreds).toBeCalled();
  });
});
