import { WebsiteUpdateAutomationStatus, WebsiteVerificationStatus } from '../../types';

export const mockMainPageUrl = 'https://www.example.com';
export const mockTermsPageUrl = 'https://www.example.com/terms';
export const mockPrivacyPageUrl = 'https://www.example.com/privacy';
export const mockRefundPageUrl = 'https://www.example.com/refund';
export const mockShippingPageUrl = 'https://www.example.com/shipping';
export const mockContactPageUrl = 'https://www.example.com/contact';

export const mockAppstoreUrl = 'https://apps.apple.com/us/app/test-app-uts/id1274679179';
export const mockPlaystoreUrl = 'https://play.google.com/store/apps/details?id=test.app.uts';
export const mockAdditionalWebsites = ['https://website1.com', 'https://website2.com'];

export const mockWebsiteVerificationPageStatusParitalSuccess = {
  terms: {
    url: '',
    verified: WebsiteVerificationStatus.FAILED,
  },
  privacy: {
    url: '',
    verified: WebsiteVerificationStatus.FAILED,
  },
  refund: {
    url: mockRefundPageUrl,
    verified: WebsiteVerificationStatus.PASSED,
  },
  shipping: {
    url: mockShippingPageUrl,
    verified: WebsiteVerificationStatus.PASSED,
  },
  contact: {
    url: mockContactPageUrl,
    verified: WebsiteVerificationStatus.PASSED,
  },
};

export const tenDaysAgoUnix = `${Math.floor(Date.now() / 1000) - 10 * 24 * 60 * 60}`;

export const mockWebsiteUpdateData = {
  current_status: WebsiteUpdateAutomationStatus.COMPLETED,
  current_status_updated_at: tenDaysAgoUnix,
  website_verification_stage: {},
  website_verification_page_status: mockWebsiteVerificationPageStatusParitalSuccess,
};

export const mockBusinessWebsiteWorkflow = {
  workflow_status: 'REVIEW',
  needs_clarification: false,
  request_under_validation: false,
  tags: [],
  rejection_reason_message: null,
  ocr_automated_check_enable: false,
};

export const getMockSubmitPayload = ({ isApp = false } = {}) => {
  return {
    platform: {
      value: isApp ? 'app' : 'website',
    },
    url: {
      value: isApp ? mockPlaystoreUrl : mockMainPageUrl,
    },
    requireCreds: {
      value: 'no',
    },
    credsUsername: {
      value: '',
    },
    credsPassword: {
      value: '',
    },
  };
};
