import React from 'react';

import { POLICY_LINKS } from 'merchant/constants/urls';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { render, screen } from 'test-utils';

import FooterLine, { LEGAL_DOCS_NAMES, getURLsByCountry } from '..';

const mockIsConfigTagEnabled = jest.fn();

jest.mock('common/i18', () => ({
  useI18Service: () => ({
    isConfigTagEnabled: mockIsConfigTagEnabled,
  }),
}));

const renderApp = (user) => render(<FooterLine user={user} />);

describe('FooterLine Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Visibility of Legal Links', () => {
    it('shows all legal links when isConfigTagEnabled returns false', () => {
      mockIsConfigTagEnabled.mockReturnValue(false);

      renderApp({
        merchant: {
          country_code: 'SG',
        },
        orgCustomCode: ORG_CUSTOM_CODE_MAP.RAZORPAY,
      });

      expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
    });

    it('hides all legal links when isConfigTagEnabled returns true', () => {
      mockIsConfigTagEnabled.mockReturnValue(true);

      renderApp({
        merchant: {
          country_code: 'SG',
        },
        orgCustomCode: ORG_CUSTOM_CODE_MAP.RAZORPAY,
      });

      expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).not.toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).not.toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).not.toBeInTheDocument();
    });

    describe('Conditional visibility based on specific tags', () => {
      it('hides only the "Merchant Agreement" when its specific tag is true', () => {
        mockIsConfigTagEnabled.mockImplementation((key) => {
          return key === 'merchant_agreements.merchant_agreement';
        });

        renderApp({
          merchant: {
            country_code: 'SG',
          },
          orgCustomCode: ORG_CUSTOM_CODE_MAP.RAZORPAY,
        });

        expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).not.toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
      });
    });

    describe('Conditional visibility based on Org', () => {
      it('hides only the "Merchant Agreement" if Razorpay Org', () => {
        mockIsConfigTagEnabled.mockReturnValue(false);

        renderApp({
          orgCustomCode: ORG_CUSTOM_CODE_MAP.RAZORPAY,
          merchant: {
            country_code: 'IN',
          },
        });

        expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).not.toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
      });

      it('Show all of the legal links if Curlec Org', () => {
        mockIsConfigTagEnabled.mockReturnValue(false);

        renderApp({
          orgCustomCode: ORG_CUSTOM_CODE_MAP.CURLEC,
          merchant: {
            country_code: 'MY',
          },
        });

        expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
      });
    });
  });
});

describe('getURLsByCountry', () => {
  const urlTests = [
    {
      countryCode: 'US',
      expectedOutput: [
        {
          label: LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT,
          link: `https://razorpay.com/us/agreement/`,
          key: 'merchant_agreement',
        },
        {
          label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
          link: `https://razorpay.com/us/tnc/`,
          key: 'terms_of_use',
        },
        {
          label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
          link: `https://razorpay.com/us/privacy-policy/`,
          key: 'privacy_policy',
        },
      ],
    },
    {
      countryCode: 'SG',
      expectedOutput: [
        {
          label: LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT,
          link: `https://razorpay.com/sg/agreement/`,
          key: 'merchant_agreement',
        },
        {
          label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
          link: `https://razorpay.com/sg/tnc/`,
          key: 'terms_of_use',
        },
        {
          label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
          link: `https://razorpay.com/sg/privacy-policy/`,
          key: 'privacy_policy',
        },
      ],
    },
    {
      countryCode: 'IN',
      expectedOutput: [
        {
          label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
          link: POLICY_LINKS.TERMS_OF_USE,
          key: 'terms_of_use',
        },
        {
          label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
          link: POLICY_LINKS.PRIVACY_POLICY,
          key: 'privacy_policy',
        },
      ],
    },
    {
      countryCode: 'MY',
      expectedOutput: [
        {
          label: LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT,
          link: POLICY_LINKS.MERCHANT_AGGREMENT_CURLEC,
          key: 'merchant_agreement',
        },
        {
          label: LEGAL_DOCS_NAMES.TERMS_OF_USE,
          link: POLICY_LINKS.TERMS_OF_USE_CURLEC,
          key: 'terms_of_use',
        },
        {
          label: LEGAL_DOCS_NAMES.PRIVACY_POLICY,
          link: POLICY_LINKS.PRIVACY_POLICY_CURLEC,
          key: 'privacy_policy',
        },
      ],
    },
  ];

  describe.each(urlTests)('for country code $countryCode', ({ countryCode, expectedOutput }) => {
    it(`should return the correct URLs for country code ${countryCode}`, () => {
      expect(getURLsByCountry(countryCode)).toEqual(expectedOutput);
    });
  });
});
