import React from 'react';

import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { render, screen } from 'test-utils';

import FooterLine, { LEGAL_DOCS_NAMES } from '..';

const mockIsConfigTagEnabled = jest.fn();

jest.mock('common/i18', () => ({
  useI18Service: () => ({
    isConfigTagEnabled: mockIsConfigTagEnabled,
  }),
}));

const mockUser = {
  orgCustomCode: ORG_CUSTOM_CODE_MAP.CURLEC,
};

const renderApp = (user = mockUser) => render(<FooterLine user={user} />);

describe('FooterLine Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Visibility of Legal Links', () => {
    it('shows all legal links when isConfigTagEnabled returns false', () => {
      mockIsConfigTagEnabled.mockReturnValue(false);

      renderApp();

      expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
    });

    it('hides all legal links when isConfigTagEnabled returns true', () => {
      mockIsConfigTagEnabled.mockReturnValue(true);

      renderApp();

      expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).not.toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).not.toBeInTheDocument();
      expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).not.toBeInTheDocument();
    });

    describe('Conditional visibility based on specific tags', () => {
      it('hides only the "Merchant Agreement" when its specific tag is true', () => {
        mockIsConfigTagEnabled.mockImplementation((key) => {
          return key === 'merchant_agreements.merchant_agreement';
        });

        renderApp();

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
        });

        expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).not.toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
      });

      it('Show all of the legal links if Curlec Org', () => {
        mockIsConfigTagEnabled.mockReturnValue(false);

        renderApp({
          orgCustomCode: ORG_CUSTOM_CODE_MAP.CURLEC,
        });

        expect(screen.queryByText(LEGAL_DOCS_NAMES.MERCHANT_AGREEMENT)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.PRIVACY_POLICY)).toBeInTheDocument();
        expect(screen.queryByText(LEGAL_DOCS_NAMES.TERMS_OF_USE)).toBeInTheDocument();
      });
    });
  });
});
