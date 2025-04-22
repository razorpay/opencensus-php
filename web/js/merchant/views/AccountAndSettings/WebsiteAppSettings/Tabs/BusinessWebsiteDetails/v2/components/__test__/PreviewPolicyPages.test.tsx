import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import PreviewPages, {
  PreviewPagesProps,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/PreviewPolicyPages';

import { WebsitePolicyPages, WebsiteSubmitModalSteps } from '../../types';
import { policyPagePublishDisclaimer } from '../constants';

const mockSetCurrentStep = jest.fn();
const mockShowNotification = jest.fn();
const defaultProps: PreviewPagesProps = {
  isMobile: false,
  isOpen: true,
  setCurrentStep: mockSetCurrentStep,
  showNotification: mockShowNotification,
  policyPagesToBeMade: [WebsitePolicyPages.TERMS],
  mode: 'live',
};

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

// Jest Mock's variables must start with mock prefix therefore have to suppress the naming convention rule
/* eslint @typescript-eslint/naming-convention: 0 */
let mockIsError = false;
let mockIsFetching = false;
let mockConsentSuccess = true;
let mockPublishStatus = 'completed';

let mockIsConsentApiSuccess = true;
let mockIsPublishApiSuccess = true;

const mockRetry = jest.fn();

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/usePolicyPagesPublish.tsx',
  () => ({
    usePolicyPagesPublish: jest.fn(() => ({
      consentMutation: {
        mutateAsync: jest.fn(() =>
          mockIsConsentApiSuccess
            ? Promise.resolve({
                success: mockConsentSuccess,
              })
            : Promise.reject(new Error('Failed to provide consent. Please try again')),
        ),
      },
      publishMutation: {
        mutateAsync: jest.fn(() =>
          mockIsPublishApiSuccess
            ? Promise.resolve({
                current_status: mockPublishStatus,
              })
            : Promise.reject(new Error('Failed to publish. Please try again')),
        ),
      },
    })),
    usePolicyPagesPreview: jest.fn(() => ({
      data: {
        data: [
          {
            section: 'terms',
            html_content: '<p>Terms and conditions Preview</p>',
          },
        ],
      },
      isError: mockIsError,
      isFetching: mockIsFetching,
      refetch: mockRetry,
    })),
  }),
);

const renderApp = (props = {}) => {
  const renderOutput = render(<PreviewPages {...defaultProps} {...props} />);
  return renderOutput;
};

describe('PreviewPages', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockIsError = false;
    mockIsFetching = false;
    mockConsentSuccess = true;
    mockPublishStatus = 'completed';
    mockIsConsentApiSuccess = true;
    mockIsPublishApiSuccess = true;
  });
  it('should render policy pages preview', () => {
    renderApp();
    expect(
      screen.getByRole('heading', {
        name: 'Review your policy pages',
      }),
    ).toBeInTheDocument();
    expect(screen.getByText('We’ve created these using your given details')).toBeInTheDocument();
  });

  it('should render policy pages preview on mobile', () => {
    renderApp({
      isMobile: true,
    });
    expect(
      screen.getByRole('heading', {
        name: 'Review your policy pages',
      }),
    ).toBeInTheDocument();
    expect(screen.getByText('We’ve created these using your given details')).toBeInTheDocument();
  });

  it('should render error message when there is an error', () => {
    mockIsError = true;
    renderApp();
    expect(screen.getByText('Preview couldn’t be loaded')).toBeInTheDocument();
  });

  it('should retry fetching preview details on retry CTA click', async () => {
    mockIsError = true;
    renderApp();
    const retryBtn = screen.getByRole('button', {
      name: 'Retry',
    });
    expect(retryBtn).toBeInTheDocument();
    await userEvent.click(retryBtn);
    expect(mockRetry).toHaveBeenCalledTimes(1);
  });

  it('should render loading state', () => {
    mockIsFetching = true;
    renderApp();
    expect(screen.getByTestId('loading-terms')).toBeInTheDocument();
  });

  it('should go back on cta click to policy creation', async () => {
    renderApp({
      policyPagesToBeMade: [WebsitePolicyPages.TERMS, WebsitePolicyPages.PRIVACY],
    });
    const goBackBtn = screen.getByRole('button', { name: 'Go back' });
    expect(goBackBtn).toBeInTheDocument();
    await userEvent.click(goBackBtn);

    expect(mockSetCurrentStep).toHaveBeenCalledWith(WebsiteSubmitModalSteps.POLICY_PAGES_CREATION);
  });

  it('should go back on cta click to add missing pages when only terms page is missing', async () => {
    renderApp({
      policyPagesToBeMade: [WebsitePolicyPages.TERMS],
    });
    const goBackBtn = screen.getByRole('button', { name: 'Go back' });
    expect(goBackBtn).toBeInTheDocument();
    await userEvent.click(goBackBtn);

    expect(mockSetCurrentStep).toHaveBeenCalledWith(
      WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES,
    );
  });

  it('should disable submit button if consent checkbox is not ticked', async () => {
    renderApp();
    const consentCheckBox = screen.getByRole('checkbox');
    expect(consentCheckBox).toBeInTheDocument();

    const submitButton = screen.getByRole('button', { name: 'Submit' });
    expect(submitButton).toBeInTheDocument();
    expect(submitButton).toBeEnabled();
    await userEvent.click(consentCheckBox);
    await waitFor(() => {
      expect(submitButton).toBeDisabled();
    });
  });

  describe('Policy Pages Preview submit', () => {
    it('should show complete pages step when submit consent and publish is success and status is completed', async () => {
      renderApp();
      const consentCheckBox = screen.getByRole('checkbox');
      expect(consentCheckBox).toBeInTheDocument();
      expect(consentCheckBox).toBeChecked();

      const submitButton = screen.getByRole('button', { name: 'Submit' });
      expect(submitButton).toBeInTheDocument();
      expect(submitButton).toBeEnabled();

      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.POLICY_PAGES_COMPLETE,
        );
      });
    });

    it('should show complete pages step when submit consent and publish is success and workflow is raised', async () => {
      mockPublishStatus = 'workflow_in_progress';
      renderApp();
      const submitButton = screen.getByRole('button', { name: 'Submit' });
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockSetCurrentStep).toHaveBeenCalledWith(
          WebsiteSubmitModalSteps.POLICY_PAGES_COMPLETE,
        );
      });
    });

    it('should show error when submit consent and publish is success but status is not expected one', async () => {
      mockPublishStatus = 'not_completed / not_workflow_in_progress';
      renderApp();
      const submitButton = screen.getByRole('button', { name: 'Submit' });
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockShowNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Something went wrong. Please try again.',
        });
      });
    });

    it('should show consent failure error when submit consent is failed', async () => {
      mockConsentSuccess = false;
      renderApp();
      const submitButton = screen.getByRole('button', { name: 'Submit' });
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockShowNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Failed to provide consent. Please try again',
        });
      });
    });

    it('should show error when consent API submit failed', async () => {
      mockIsConsentApiSuccess = false;
      renderApp();
      const submitButton = screen.getByRole('button', { name: 'Submit' });
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockShowNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Failed to provide consent. Please try again',
        });
      });
    });

    it('should show error when publish API submit failed', async () => {
      mockIsPublishApiSuccess = false;
      renderApp();
      const submitButton = screen.getByRole('button', { name: 'Submit' });
      await userEvent.click(submitButton);
      await waitFor(() => {
        expect(mockShowNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Failed to publish. Please try again',
        });
      });
    });
  });

  describe('Policy Pages Publish Discalimer', () => {
    it('should show disclaimer content', async () => {
      renderApp();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'this disclaimer.',
        }),
      );
      expect(screen.getByText(policyPagePublishDisclaimer)).toBeInTheDocument();
    });

    it('should toggle visiblity of disclaimer on ack cta', async () => {
      renderApp();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'this disclaimer.',
        }),
      );
      const disclaimerContent = screen.getByText(policyPagePublishDisclaimer);
      expect(disclaimerContent).toBeInTheDocument();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'this disclaimer.',
        }),
      );
      await waitFor(() => {
        expect(disclaimerContent).not.toBeInTheDocument();
      });
    });
  });
});
