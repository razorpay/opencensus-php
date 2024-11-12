import React from 'react';
import { render, screen, waitFor, checkIfComponentIsEmpty, userEvent } from 'test-utils';

import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

import { mockBusinessWebsiteWorkflow, mockWebsiteUpdateData } from '../../__test__/mocks/fixtures';
import { WebsiteUpdateAutomationStatus } from '../../types';
import PrimaryWebsiteWorkflowStatus from '../PrimaryWebsiteWorkflowStatus';

const mockOnFixMissingPages = jest.fn();
const mockOnReplyClick = jest.fn();

const defaultProps = {
  websiteUpdateData: mockWebsiteUpdateData,
  businessWebsiteWorkflow: mockBusinessWebsiteWorkflow,
  onFixMissingPages: mockOnFixMissingPages,
  onReplyClick: mockOnReplyClick,
  org: {
    business_name: 'Rzp',
  },
};

const renderApp = (props = {}) => {
  const renderOutput = render(<PrimaryWebsiteWorkflowStatus {...defaultProps} {...props} />);
  return renderOutput;
};

const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  __esModule: true,
  ...(jest.requireActual('react-router-dom') as unknown as Record<string, unknown>),
  useNavigate: () => mockNavigate,
}));

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

describe('Business website automation - PrimaryWebsiteWorkflowStatus', () => {
  it('should not show anything when no condition for workflow matches i.e deafult state', () => {
    renderApp({
      websiteUpdateData: {
        current_status: 'Non Existent Status',
      },
    });
    checkIfComponentIsEmpty();
  });

  it('should show success alert', async () => {
    renderApp({
      websiteUpdateData: {
        current_status: WebsiteUpdateAutomationStatus.COMPLETED,
        current_status_updated_at: Math.floor(Date.now() / 1000),
      },
    });
    expect(screen.getByText('Your website has been successfully verified')).toBeInTheDocument();
    expect(
      screen.getByText(
        'To start accepting payments, you’ll need to download API keys and integrate them on the website',
      ),
    ).toBeInTheDocument();

    expect(screen.getByRole('button', { name: 'Download API Keys' })).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Download API Keys' }));
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith(ROUTES_INFO.API_KEYS);
    });
  });

  // TODO: FIX POST GO LIVE
  it.skip('should show fix policy pages alert', async () => {
    renderApp({
      websiteUpdateData: {
        current_status: WebsiteUpdateAutomationStatus.IN_PROGRESS,
        current_status_updated_at: Math.floor(Date.now() / 1000),
        website_verification_stage: {
          worklfow_exist: false,
          bvs_check_status: 'FAILED',
        },
      },
    });
    expect(
      screen.getByText('We found a few policy details missing on your website'),
    ).toBeInTheDocument();
    expect(screen.getByText('Kindly update and add your website details')).toBeInTheDocument();

    expect(screen.getByRole('button', { name: 'Update now' })).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Update now' }));
    await waitFor(() => {
      expect(mockOnFixMissingPages).toHaveBeenCalled();
    });
  });

  it('should show bvs check under review alert', () => {
    renderApp({
      websiteUpdateData: {
        current_status: WebsiteUpdateAutomationStatus.IN_PROGRESS,
        current_status_updated_at: Math.floor(Date.now() / 1000),
      },
    });
    expect(
      screen.getByText('Your website verification request is under review'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('We’ll verify your details and share an update within 10 minutes'),
    ).toBeInTheDocument();
  });

  it('should show workflow under review alert', () => {
    renderApp({
      websiteUpdateData: {
        current_status: WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS,
        current_status_updated_at: Math.floor(Date.now() / 1000),
      },
      businessWebsiteWorkflow: {
        ocr_automated_check_enable: true,
      },
    });
    expect(
      screen.getByText('Your website verification request is under review'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(/We’ll verify your details and share an update/, {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  it('should show needs clarification review alert', async () => {
    renderApp({
      websiteUpdateData: {
        current_status: WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS,
        current_status_updated_at: Math.floor(Date.now() / 1000),
      },
      businessWebsiteWorkflow: {
        workflow_status: 'open',
        needs_clarification: 'comment by ops agent',
        request_under_validation: false,
        tags: ['awaiting-customer-response'],
      },
    });
    expect(
      screen.getByText('Our team needs a few more details to verify your website'),
    ).toBeInTheDocument();
    expect(screen.getByText(/comment by ops agent/)).toBeInTheDocument();
    const addReplyButton = screen.getByRole('button', { name: 'Resolve now' });
    expect(addReplyButton).toBeInTheDocument();
    await userEvent.click(addReplyButton);
    await waitFor(() => {
      expect(mockOnReplyClick).toHaveBeenCalled();
    });
  });

  it('should show rejection alert', () => {
    renderApp({
      websiteUpdateData: {
        current_status: WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS,
        current_status_updated_at: Math.floor(Date.now() / 1000),
      },
      businessWebsiteWorkflow: {
        workflow_status: 'rejected',
        needs_clarification: true,
        request_under_validation: false,
        tags: ['awaiting-customer-response'],
        rejection_reason_message: 'Test rejection message',
      },
    });
    expect(
      screen.getByText('Our team has rejected your website upon careful verification'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(/Test rejection message/, {
        exact: false,
      }),
    ).toBeInTheDocument();
  });
});
