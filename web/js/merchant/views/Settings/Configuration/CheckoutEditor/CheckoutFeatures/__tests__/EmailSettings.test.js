import React from 'react';

import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';
import EmailSettings from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/EmailSettings';
import { EMAIL_SETTINGS_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { render, screen, fireEvent } from 'test-utils';

jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context/index', () => ({
  useCheckoutEditor: jest.fn(),
}));

describe('EmailSettings', () => {
  const handleEmailToggle = jest.fn();
  const handleEmailValueChange = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders the EmailSettings component with the correct title', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.EMAIL]: {
          isEnabled: false,
          value: EmailLessCheckoutConfigOptions.NO,
        },
      },
      handleEmailToggle,
      handleEmailValueChange,
    });

    render(<EmailSettings />);

    expect(screen.getByText(EMAIL_SETTINGS_DEFAULT_VALUE.title)).toBeInTheDocument();
    expect(screen.getByText(EMAIL_SETTINGS_DEFAULT_VALUE.subTitle)).toBeInTheDocument();
  });

  it('shows radio buttons when email feature is enabled', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.EMAIL]: {
          isEnabled: true,
          value: EmailLessCheckoutConfigOptions.OPTIONAL,
        },
      },
      handleEmailToggle,
      handleEmailValueChange,
    });

    render(<EmailSettings />);

    expect(
      screen.getByTestId(`email-settings-${EmailLessCheckoutConfigOptions.OPTIONAL}`),
    ).toBeInTheDocument();

    expect(
      screen.getByTestId(`email-settings-${EmailLessCheckoutConfigOptions.MANDATORY}`),
    ).toBeInTheDocument();
  });

  it('calls handleEmailToggle when the feature toggle is clicked', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.EMAIL]: {
          isEnabled: false,
          value: EmailLessCheckoutConfigOptions.NO,
        },
      },
      handleEmailToggle,
      handleEmailValueChange,
    });

    render(<EmailSettings />);

    const toggle = screen.getByLabelText(`enable-${CHECKOUT_EDITOR_FIELDS.EMAIL}`);
    fireEvent.click(toggle);
    expect(handleEmailToggle).toHaveBeenCalledWith(true);
  });

  it('calls handleEmailValueChange when a radio button is selected', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.EMAIL]: {
          isEnabled: true,
          value: EmailLessCheckoutConfigOptions.OPTIONAL,
        },
      },
      handleEmailToggle,
      handleEmailValueChange,
    });

    render(<EmailSettings />);

    const mandatoryRadio = screen.getByTestId(
      `email-settings-${EmailLessCheckoutConfigOptions.MANDATORY}`,
    );
    fireEvent.click(mandatoryRadio);
    expect(handleEmailValueChange).toHaveBeenCalledWith(EmailLessCheckoutConfigOptions.MANDATORY);
  });
});
