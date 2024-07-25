import React from 'react';
import { render, screen } from 'test-utils';

import CompletedPolicyPages, { PolicyPagesCompleteProps } from '../CompletedPolicyPages';
import { WebsitePolicyPages } from '../../types';

const defaultProps: PolicyPagesCompleteProps = {
  isOpen: true,
  isMobile: false,
  onDismiss: jest.fn(),
  pagesBeingVerified: [
    WebsitePolicyPages.REFUND,
    WebsitePolicyPages.PRIVACY,
    WebsitePolicyPages.TERMS,
  ],
};

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/utils',
  () => {
    const originalModule = jest.requireActual(
      'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/utils',
    );
    return {
      __esModule: true,
      ...originalModule,
      getReviewPagesData: jest.fn(() => {
        return {
          verifiedPages: {
            shipping: {
              value: 'https://www.gurusakhi.com',
            },
            contact: {
              value: 'https://www.gurusakhi.com',
            },
          },
          missingPages: {
            terms: {
              value: 'https://www.merchant.razorpay.com',
            },
            privacy: {
              value: 'https://www.test.com',
            },
            refund: {
              value: 'https://www.test.com',
            },
          },
          verifiedPagesKeys: ['shipping', 'contact'],
          missingPagesKeys: ['refund', 'privacy', 'terms'],
        };
      }),
    };
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

const renderApp = (props = defaultProps) => {
  const renderOutput = render(<CompletedPolicyPages {...props} />);
  return renderOutput;
};

describe('Completed Policy Pages', () => {
  beforeAll(() => {
    window.APP_ENV = 'production';
  });
  it('should render verified & completed sections both', () => {
    renderApp();

    expect(screen.getByTestId('completed-pages')).toBeInTheDocument();
  });

  it('should render page names', () => {
    renderApp();

    expect(screen.getByText('Cancellations and Refunds')).toBeInTheDocument();
    expect(screen.getByText('Terms and Conditions')).toBeInTheDocument();
    expect(screen.getByText('Privacy Policy')).toBeInTheDocument();
  });

  it('should render page verification badges', () => {
    renderApp();

    const verificationBadges = screen.getAllByText('Verified');
    expect(verificationBadges).toHaveLength(2);
    const createdByRazorpay = screen.getAllByText('Created by Razorpay');
    expect(createdByRazorpay).toHaveLength(1);
  });

  it('should render page links', () => {
    renderApp();

    const verificationLinks = screen.getAllByText('https://www.test.com');
    expect(verificationLinks).toHaveLength(2);

    const createdByRazorpayLinks = screen.getAllByText('https://www.merchant.razorpay.com');
    expect(createdByRazorpayLinks).toHaveLength(1);
  });
});
