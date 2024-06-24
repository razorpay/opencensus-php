import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import { User } from 'common/typings';

import {
  mockPrivacyPageUrl,
  mockTermsPageUrl,
  mockWebsiteVerificationPageStatusParitalSuccess,
} from '../../__test__/mocks/fixtures';
import WebsiteFixModal from '../WebsiteFixModal';

const mockOnDismiss = jest.fn();
const mockHandleMainPageSubmit = jest.fn();

const defaultProps = {
  isMobile: false,
  isOpen: true,
  onDismiss: mockOnDismiss,
  handlePolicyPageSubmit: mockHandleMainPageSubmit,
  user: {} as User,
};

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useBusinessWebsiteData',
  () => {
    return jest.fn(() => ({
      websiteUpdateData: {
        website_verification_page_status: mockWebsiteVerificationPageStatusParitalSuccess,
      },
    }));
  },
);

const renderApp = (props = {}) => {
  const renderOutput = render(<WebsiteFixModal {...defaultProps} {...props} />);
  return renderOutput;
};

describe('Business website automation -  WebsiteFixModal', () => {
  const testFormInputAndSubmit = async () => {
    expect(screen.getByText('Required policy pages on your website')).toBeInTheDocument();

    const termsAndConditionsLink = screen.getByRole('textbox', {
      name: 'Terms and Conditions link',
    });
    expect(termsAndConditionsLink).toBeInTheDocument();
    await userEvent.type(termsAndConditionsLink, mockTermsPageUrl);

    const privacyPolicyLink = screen.getByRole('textbox', {
      name: 'Privacy Policy link',
    });
    expect(privacyPolicyLink).toBeInTheDocument();
    await userEvent.type(privacyPolicyLink, mockPrivacyPageUrl);

    expect(screen.getByText('Policy pages found on your website')).toBeInTheDocument();

    expect(screen.getByTestId('verified-policy-page-card-contact')).toBeInTheDocument();
    expect(screen.getByTestId('verified-policy-page-card-shipping')).toBeInTheDocument();
    expect(screen.getByTestId('verified-policy-page-card-refund')).toBeInTheDocument();
  };

  it('should render the input and submit the form', async () => {
    renderApp();
    expect(screen.getByText('Submit details for verification')).toBeInTheDocument();

    const submitButton = screen.getByRole('button', { name: 'Submit' });
    await testFormInputAndSubmit();
    expect(submitButton).toBeEnabled();
    await userEvent.click(submitButton);
    await waitFor(() => {
      expect(mockHandleMainPageSubmit).toHaveBeenCalled();
    });
  });

  it('should render the input and submit the form for mobile', async () => {
    renderApp({
      isMobile: true,
    });
    expect(screen.getByText('Submit details for verification')).toBeInTheDocument();

    const submitButton = screen.getByRole('button', { name: 'Proceed' });
    await testFormInputAndSubmit();
    expect(submitButton).toBeEnabled();
    await userEvent.click(submitButton);
    await waitFor(() => {
      expect(mockHandleMainPageSubmit).toHaveBeenCalled();
    });
  });

  it('should dismiss the modal', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Submit details for verification')).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    await waitFor(() => {
      expect(mockOnDismiss).toHaveBeenCalled();
    });
  });
});
