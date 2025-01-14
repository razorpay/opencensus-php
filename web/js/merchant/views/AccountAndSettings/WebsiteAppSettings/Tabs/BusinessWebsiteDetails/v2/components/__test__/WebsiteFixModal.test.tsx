import React, { useState } from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import { User } from 'common/typings';
import {
  mockWebsiteVerificationPageStatusParitalSuccess,
  mockRefundPageUrl,
  mockShippingPageUrl,
  mockContactPageUrl,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/__test__/mocks/fixtures';
import WebsiteFixModal from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/WebsiteFixModal';
import { PolicyPageFormData } from '../../types';
import { getInitialPolicyPagesFormState } from '../utils';

const mockOnDismiss = jest.fn();
const mockHandleMainPageSubmit = jest.fn();

const defaultProps = {
  isMobile: false,
  isOpen: true,
  onDismiss: mockOnDismiss,
  onCreateAllPolicyPagesButtonClick: () => undefined,
  handlePolicyPageSubmit: mockHandleMainPageSubmit,
  user: {} as User,
  org: {
    business_name: 'Razorpay',
  },
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

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
  () => {
    const originalModule = jest.requireActual(
      'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
    );
    return {
      __esModule: true,
      ...originalModule,
      getWebsiteCount: jest.fn(() => {
        return 0;
      }),
    };
  },
);

const App = (props) => {
  const { missingPages, missingPagesKeys, verifiedPages, verifiedPagesKeys } =
    getInitialPolicyPagesFormState(mockWebsiteVerificationPageStatusParitalSuccess);

  const [policyFormState, setPolicyFormState] = useState<PolicyPageFormData>(missingPages);
  return (
    <WebsiteFixModal
      {...defaultProps}
      {...props}
      formState={policyFormState}
      setFormState={setPolicyFormState}
      verifiedPages={verifiedPages}
      verifiedPagesKeys={verifiedPagesKeys}
      missingPagesKeys={missingPagesKeys}
      missingPages={missingPages}
    />
  );
};

const renderApp = (props = {}) => {
  const renderOutput = render(<App {...props} />);
  return renderOutput;
};

describe('Business website automation -  WebsiteFixModal', () => {
  it('should render component header & CTAs', () => {
    renderApp();
    expect(screen.getByText('Required policy pages on your website')).toBeInTheDocument();
    expect(
      screen.getByText(
        `If you don’t have any of these required pages/details, we'll help you create them.`,
      ),
    ).toBeInTheDocument();

    const submitButton = screen.getByRole('button', { name: 'Submit' });
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    expect(submitButton).toBeEnabled();
    expect(cancelButton).toBeInTheDocument();
  });

  it('should render verified pages & links', () => {
    renderApp();

    expect(screen.getByText('Policy pages found on your website')).toBeInTheDocument();
    expect(screen.getByText(`Cancellations and Refunds`)).toBeInTheDocument();
    expect(screen.getByText(`Contact Us`)).toBeInTheDocument();
    expect(screen.getByText(`Shipping Policy`)).toBeInTheDocument();

    expect(screen.getByText(mockContactPageUrl)).toBeInTheDocument();
    expect(screen.getByText(mockRefundPageUrl)).toBeInTheDocument();
    expect(screen.getByText(mockShippingPageUrl)).toBeInTheDocument();
  });

  it('should render missing pages & radio btns', () => {
    renderApp();

    expect(screen.getByText('Terms and Conditions')).toBeInTheDocument();
    expect(screen.getByText(`Privacy Policy`)).toBeInTheDocument();

    expect(screen.getAllByTestId('merchant-link')).toHaveLength(2);
    expect(screen.getAllByTestId('create-via-rzp')).toHaveLength(2);
  });

  it('should render error msg on submit click without filling inputs', async () => {
    renderApp();

    const submitButton = screen.getByRole('button', { name: 'Submit' });
    await userEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getAllByText('Please select an option')).toHaveLength(2);
    });
  });
});
