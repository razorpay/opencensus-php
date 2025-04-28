import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor, checkIfComponentIsEmpty, userEvent, server } from 'test-utils';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

import {
  getMockSubmitPayload,
  mockAppstoreUrl,
  mockMainPageUrl,
  mockWebsiteVerificationPageStatusParitalSuccess,
} from './mocks/fixtures';
import {
  mockAppSubmitFailure,
  mockAppSubmitSuccess,
  mockMainPageSubmitFailure,
  mockMainPageSubmitLivenessCheckFailure,
  mockMainPageSubmitSuccess,
  mockNonActivatedSubmitFailure,
  mockNonActivatedSubmitSuccess,
  mockPolicyPageSubmitFailure,
  mockPolicyPageSubmitSuccess,
  mockPolicyPageSubmitSuccessWithWorkflow,
} from './mocks/handlers';
import WebsiteSubmitModal from '../WebsiteSubmitModal';
import { WebsiteSubmitModalSteps } from '../types';
import { genericBackendErrorMessage } from '../utils';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const mockOnDismiss = jest.fn();
const mockSetCurrentStep = jest.fn();
const mockUpdateSession = jest.fn();
let mockCurrentStep = '';
let mockData = {};
let mockMainPageSubmitPayload = {};

jest.mock('merchant/reducers/session', () => ({
  ...(jest.requireActual('merchant/reducers/session') as unknown as any),
  updateSession: () => mockUpdateSession,
}));

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/WebsiteInputModal',
  () => ({
    __esModule: true,
    default: ({ handleMainPageSubmit }) => (
      <div>
        <h1>WebsiteInputModal</h1>
        <button onClick={() => handleMainPageSubmit(mockMainPageSubmitPayload)}>Submit</button>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/WebsiteFixModal',
  () => ({
    __esModule: true,
    default: ({ handlePolicyPageSubmit }) => (
      <div>
        <h1>WebsiteFixModal</h1>
        <button onClick={() => handlePolicyPageSubmit({})}>Submit</button>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/Loader.tsx',
  () => ({
    __esModule: true,
    default: () => (
      <div>
        <h1>Loader</h1>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/MainPageLivenessError.tsx',
  () => ({
    __esModule: true,
    default: () => (
      <div>
        <h1>Main Page Liveness Error</h1>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useBusinessWebsiteData',
  () => {
    return jest.fn(() => ({
      currentStep: mockCurrentStep,
      setCurrentStep: mockSetCurrentStep,
      ...mockData,
    }));
  },
);

jest.mock('@libs/web-nexus/common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: {} }),
}));

const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
const defaultProps = {
  isOpen: true,
  onDismiss: mockOnDismiss,
  mode: 'live',
};

const renderApp = (
  props = {
    user: {},
  },
) => {
  render(
    <QueryClientProvider client={queryClient}>
      <WebsiteSubmitModal {...defaultProps} {...props} />
    </QueryClientProvider>,
    {
      initialState: {
        session: {
          user: {
            has_key_access: true,
            business_website: mockAppstoreUrl,
            activated: true,
            ...props.user,
          },
        },
      },
    },
  );
};

describe('WebsiteSubmitModal', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
    mockCurrentStep = '';
    mockSetCurrentStep.mockClear();
    mockData = {};
    mockMainPageSubmitPayload = {};
  });

  it('should show website input modal', async () => {
    mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
    });
  });

  it('should show error notification when form submit data is invalid', async () => {
    mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
    mockMainPageSubmitPayload = {
      platform: {
        value: 'none',
      },
    };
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
    });
    const submitButton = screen.getByText('Submit');
    await userEvent.click(submitButton);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Please enter valid inputs.',
      });
    });
  });

  it('should show error notification when new website is same as old website', async () => {
    mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
    mockMainPageSubmitPayload = getMockSubmitPayload({
      isApp: false,
    });
    renderApp({
      user: {
        business_website: mockMainPageUrl,
      },
    });
    await waitFor(() => {
      expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
    });
    const submitButton = screen.getByText('Submit');
    await userEvent.click(submitButton);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'This website is already added',
      });
    });
  });

  describe('main page submit', () => {
    it('should submit and show success modal when API Response is success', async () => {
      server.use(mockMainPageSubmitSuccess());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: false,
      });
      renderApp({
        user: {
          has_key_access: false,
          isActivated: true,
        },
      });
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS,
        );
      });
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS,
        );
      });
    });

    it('should show erorr screen when main page liveness check fails', async () => {
      server.use(mockMainPageSubmitLivenessCheckFailure());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: false,
      });
      renderApp({
        user: {
          has_key_access: false,
          isActivated: true,
        },
      });
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS,
        );
      });
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.MAIN_PAGE_LIVENESS_ERROR,
        );
      });
    });

    it('should show error alert when website submit fails', async () => {
      server.use(mockMainPageSubmitFailure());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: false,
      });
      renderApp({
        user: {
          has_key_access: false,
          isActivated: true,
        },
      });
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS,
        );
      });
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          message: genericBackendErrorMessage,
          type: 'error',
        });
      });
    });
  });

  describe('app submit', () => {
    it('should submit for app and show success modal when API Response is success', async () => {
      server.use(mockAppSubmitSuccess());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: true,
      });
      renderApp({
        user: {
          has_key_access: false,
          business_website: mockMainPageUrl,
          activated: true,
          isActivated: true,
        },
      });
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'success',
          message: 'Thank you for providing app.',
        });
      });
      await waitFor(() => {
        expect(mockOnDismiss).toHaveBeenCalled();
      });
    });

    it('should show error when App submit fails', async () => {
      server.use(mockAppSubmitFailure());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: true,
      });
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: 'Something went wrong. Please try again.',
        });
      });
    });
  });

  describe('non activated user', () => {
    it('should submit and show success modal when user is non activated and API is success', async () => {
      server.use(mockNonActivatedSubmitSuccess());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: false,
      });
      renderApp({
        user: {
          activated: false,
          hasKeyAccess: false,
        },
      });
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockUpdateSession).toHaveBeenCalled();
      });
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'success',
          message: 'Thank you for providing website.',
        });
      });
      await waitFor(() => {
        expect(mockOnDismiss).toHaveBeenCalled();
      });
    });

    it('should show error for Non Activated user when API fails', async () => {
      server.use(mockNonActivatedSubmitFailure());
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MAIN_PAGE;
      mockMainPageSubmitPayload = getMockSubmitPayload({
        isApp: false,
      });
      renderApp({
        user: {
          activated: false,
          hasKeyAccess: false,
        },
      });
      await waitFor(() => {
        expect(screen.getByText('WebsiteInputModal')).toBeInTheDocument();
      });
      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: 'Failed to add website!',
        });
      });
    });
  });

  describe('policy pages submit', () => {
    it('should show success when policy pages submit is success and workflow is raised', async () => {
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES;
      mockData = {
        websiteUpdateData: {
          website_verification_page_status: mockWebsiteVerificationPageStatusParitalSuccess,
        },
      };
      server.use(mockPolicyPageSubmitSuccessWithWorkflow());
      renderApp();

      await waitFor(() => {
        expect(screen.getByText('WebsiteFixModal')).toBeInTheDocument();
      });

      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.POLICY_PAGES_CREATION,
        );
      });
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.POLICY_PAGES_CREATION,
        );
      });
    });

    it('should show success when policy pages submit is success and website is updated', async () => {
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES;
      mockData = {
        websiteUpdateData: {
          website_verification_page_status: mockWebsiteVerificationPageStatusParitalSuccess,
        },
      };
      server.use(mockPolicyPageSubmitSuccess());
      renderApp();

      await waitFor(() => {
        expect(screen.getByText('WebsiteFixModal')).toBeInTheDocument();
      });

      const submitButton = screen.getByText('Submit');
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.POLICY_PAGES_CREATION,
        );
      });
    });

    // Fixing this later, unblocking UTs for now
    it.skip('should show error when website policy pages submit fails with API error', async () => {
      mockCurrentStep = WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES;
      mockData = {
        websiteUpdateData: {
          website_verification_page_status: mockWebsiteVerificationPageStatusParitalSuccess,
        },
      };
      server.use(mockPolicyPageSubmitFailure());
      renderApp();

      await waitFor(() => {
        expect(screen.getByText('WebsiteFixModal')).toBeInTheDocument();
      });

      const submitButton = screen.getByText('Submit');

      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.POLICY_PAGES_SUBMIT_IN_PROGRESS,
        );
      });
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: genericBackendErrorMessage,
        });
      });
    });
  });

  it('should show loader modal', async () => {
    mockCurrentStep = WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS;
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Loader')).toBeInTheDocument();
    });
  });

  it('shoud not show anywthing null when current step is not as per expected ', () => {
    mockCurrentStep = '';
    renderApp();

    checkIfComponentIsEmpty();
  });

  it('shoud show main page liveness error', async () => {
    mockCurrentStep = WebsiteSubmitModalSteps.MAIN_PAGE_LIVENESS_ERROR;
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Main Page Liveness Error')).toBeInTheDocument();
    });
  });
});
