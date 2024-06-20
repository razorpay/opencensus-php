import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import WorkflowAndAlerts from '../WorkflowAndAlerts';

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/PrimaryWebsiteWorkflowStatus',
  () => ({
    __esModule: true,
    default: ({ onReplyClick }) => (
      <div>
        <h1>Primary website workflow</h1>
        <button data-testid="primary-website-btn" onClick={onReplyClick}>
          Add Reply
        </button>
      </div>
    ),
  }),
);

jest.mock('merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus', () => ({
  __esModule: true,
  default: ({ onReplyClick }) => (
    <div>
      <h1>Additonal website workflow</h1>
      <button data-testid="additional-website-btn" onClick={onReplyClick}>
        Add Reply
      </button>
    </div>
  ),
}));

const mockOnFixMissingPages = jest.fn();
const mockOpenModal = jest.fn();

const defaultProps = {
  businessWebsiteWorkflow: {},
  websiteUpdateData: {},
  openModal: mockOpenModal,
  onFixMissingPages: mockOnFixMissingPages,
};

const renderApp = (props = {}) => render(<WorkflowAndAlerts {...defaultProps} {...props} />);

describe('Business website automation - WorkflowAndAlerts', () => {
  it('should show primary website workflow alert', async () => {
    renderApp();
    expect(screen.getByText('Primary website workflow')).toBeInTheDocument();

    const addReplyBtn = screen.getByTestId('primary-website-btn');
    expect(addReplyBtn).toBeInTheDocument();
    await userEvent.click(addReplyBtn);

    expect(mockOpenModal).toBeCalled();
  });

  it('should show primary website workflow alert for first time website add', async () => {
    renderApp({
      businessWebsiteWorkflow: {
        permission: 'edit_merchant_website_detail',
      },
    });
    expect(screen.getByText('Primary website workflow')).toBeInTheDocument();

    const addReplyBtn = screen.getByTestId('primary-website-btn');
    expect(addReplyBtn).toBeInTheDocument();
    await userEvent.click(addReplyBtn);

    expect(mockOpenModal).toBeCalled();
  });

  it('should show additional website workflow alert', async () => {
    renderApp();
    expect(screen.getByText('Additonal website workflow')).toBeInTheDocument();

    const addReplyBtn = screen.getByTestId('additional-website-btn');
    expect(addReplyBtn).toBeInTheDocument();
    await userEvent.click(addReplyBtn);

    expect(mockOpenModal).toBeCalled();
  });
});
