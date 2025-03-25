import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { useMobile } from 'common/hooks/useMobile';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { mockMainPageUrl } from './mocks/fixtures';
import BusinessWebsiteDetails from '../Wrapper';
import { WebsiteSubmitModalSteps } from '../types';
import { FINAL_STEP_CONFIG } from '@dashboards/payments/views/Capital/CashAdvanceV2/constants';

const openModalSpy = jest.spyOn(ModalActions, 'openModal');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const mockSetCurrentStep = jest.fn();

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/RenderWebsites',
  () => ({
    __esModule: true,
    default: ({ user }) => (
      <div>
        <h1>RenderWebsites</h1>
        <p>{user?.business_website}</p>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/WorkflowAndAlerts',
  () => ({
    __esModule: true,
    default: ({ onFixMissingPages }) => (
      <div>
        <h1>WorkflowAndAlerts</h1>
        <button onClick={onFixMissingPages}>onFixMissingPages</button>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/WebsiteSubmitModal',
  () => ({
    __esModule: true,
    default: ({ onDismiss }) => (
      <div>
        <h1>WebsiteSubmitModal</h1>
        <button onClick={onDismiss}>onDismiss</button>
      </div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useBusinessWebsiteData',
  () => {
    return jest.fn(() => ({
      isWebsiteDetailsFetching: false,
      isWebsiteDetailsFetchError: false,
      setCurrentStep: mockSetCurrentStep,
    }));
  },
);

const defaultProps = {
  fetchWorkflowStatus: jest.fn(),
  openModal: jest.fn(),
  closeModal: jest.fn(),
};

const renderApp = (
  props = {
    user: {},
  },
) => {
  render(<BusinessWebsiteDetails {...defaultProps} {...props} />, {
    initialState: {
      session: {
        user: {
          business_website: mockMainPageUrl,
          isAdminOrOwner: true,
          ...props.user,
        },
        org: { business_name: 'Rzp' },
      },
      workflows: {},
    },
  });
};

describe('Business website automation', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
    showNotificationSpy.mockClear();
    (useMobile as jest.Mock).mockReset();
  });

  it('should show business website details', () => {
    renderApp();
    expect(
      screen.getByRole('heading', { name: 'Business website/app details' }),
    ).toBeInTheDocument();
  });

  it('should show CTA for first time website add or is a KLA merchant', async () => {
    renderApp({
      user: {
        business_website: undefined,
        isAdminOrOwner: true,
        isOwner: true,
        has_key_access: false,
      },
    });
    const button = screen.getByRole('button', { name: 'Add website/app details' });
    expect(button).toBeInTheDocument();

    await userEvent.click(button);

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  it('should show CTA for additional website add when user already has business website and is not a kla merchant', async () => {
    renderApp({
      user: {
        business_website: mockMainPageUrl,
        isAdminOrOwner: true,
        isOwner: true,
        has_key_access: true,
      },
    });
    const button = screen.getByRole('button', { name: 'Add additional website/app details' });
    expect(button).toBeInTheDocument();

    await userEvent.click(button);

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  it('should show current business website', () => {
    renderApp();
    expect(screen.getByText('RenderWebsites')).toBeInTheDocument();
    expect(screen.getByText(mockMainPageUrl)).toBeInTheDocument();
  });

  it('should show workflow and other alerts', async () => {
    renderApp();
    expect(screen.getByText('WorkflowAndAlerts')).toBeInTheDocument();
    const button = screen.getByText('onFixMissingPages');
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockSetCurrentStep).toHaveBeenCalledWith(
        WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES,
      );
    });
  });

  it('should show wesite submit modal step', async () => {
    renderApp();
    expect(screen.getByText('WebsiteSubmitModal')).toBeInTheDocument();
    const button = screen.getByText('onDismiss');
    expect(button).toBeInTheDocument();
    await userEvent.click(button);
    await waitFor(() => {
      expect(mockSetCurrentStep).toHaveBeenCalledWith(WebsiteSubmitModalSteps.ADD_MAIN_PAGE);
    });
  });

  it('should show business website details on mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp({
      user: {
        business_website: undefined,
        isAdminOrOwner: true,
        isOwner: true,
      },
    });
    expect(
      screen.getByRole('heading', { name: 'Business website/app details' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Add website/app details' })).toBeInTheDocument();
    expect(screen.getByText('RenderWebsites')).toBeInTheDocument();
    expect(screen.getByText('WorkflowAndAlerts')).toBeInTheDocument();
    expect(screen.getByText('WebsiteSubmitModal')).toBeInTheDocument();
  });

  it('should show error notification when website details fetch fails', async () => {
    const errorMessage = 'Something went wrong. Please try again.';
    jest.mock(
      'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useBusinessWebsiteData',
      () => {
        return jest.fn(() => ({
          isWebsiteDetailsFetching: false,
          isWebsiteDetailsFetchError: true,
          error: {
            message: errorMessage,
          },
        }));
      },
    );
    renderApp();
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: errorMessage,
      });
    });
  });
});
