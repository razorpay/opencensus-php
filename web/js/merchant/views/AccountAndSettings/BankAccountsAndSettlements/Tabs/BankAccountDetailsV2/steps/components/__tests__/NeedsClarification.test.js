import { testBottomLinks, testFileUploadSection } from './mocks/fixtures/common';
import { workflowData, applyForm } from './mocks/fixtures/NeedsClarification';
import * as fetchWorkflow from 'merchant/reducers/workflows';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import {
  fetchWorkflowStatusSuccess,
  submitClarificationSuccess,
  uploadDocumentSuccess,
  submitClarificationError,
} from './mocks/handlers';

describe('NeedsClarificationFlow', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  const fetchWorkflowSpy = jest.spyOn(fetchWorkflow, 'fetchWorkflowStatus');
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

  const renderApp = ({ props, bankWorkflows } = {}) => {
    return render(
      <BankAccountUpdateFlow
        defaultView={BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION}
        {...props}
      />,
      {
        initialState: {
          workflows: {
            bank_detail_update: {
              loading: false,
              error: null,
              ...workflowData,
              ...bankWorkflows,
            },
          },
        },
      },
    );
  };

  beforeEach(() => {
    showNotificationSpy.mockClear();
    fetchWorkflowSpy.mockClear();
    closeModalSpy.mockClear();
    server.use(fetchWorkflowStatusSuccess(workflowData));
  });

  test('should render need clarification modal title', () => {
    renderApp();
    expect(screen.getByText('Update details as per the instructions below')).toBeInTheDocument();
  });

  test('should render admin response message in popup', () => {
    renderApp();
    expect(screen.getByText('From Razorpay support')).toBeInTheDocument();
    expect(screen.getByText(`“${workflowData.needs_clarification}”`)).toBeInTheDocument();
  });

  test('should render shimmer when workflow loading', () => {
    renderApp({
      bankWorkflows: {
        loading: true,
      },
    });
    expect(screen.getByTestId('nc-shimmer')).toBeInTheDocument();
  });

  test('should show no clarification required notification when workflow status is not open and approved', async () => {
    server.use(
      fetchWorkflowStatusSuccess({
        ...workflowData,
        workflow_status: 'close',
      }),
    );
    renderApp();
    expect(fetchWorkflowSpy).toHaveBeenCalledTimes(1);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: `No clarification required for Change your bank account workflow`,
      });
    });
  });

  test('should show already responded notification when workflow tags includes customer responded', async () => {
    server.use(
      fetchWorkflowStatusSuccess({
        ...workflowData,
        tags: ['customer-responded'],
      }),
    );
    renderApp();
    expect(fetchWorkflowSpy).toHaveBeenCalledTimes(1);
    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: `You've already responded to Change your bank account workflow`,
      });
    });
  });

  testBottomLinks(renderApp, 'Submit details');

  describe('TextArea', () => {
    test('should render text area for reply response', () => {
      renderApp();
      expect(screen.getByText('Note')).toBeInTheDocument();
      expect(screen.getByText('From Razorpay support')).toBeInTheDocument();
    });

    test('should add note on change action in textarea', async () => {
      renderApp();
      const textArea = screen.getByRole('textbox');
      const response = 'uploaded new document with clear visibility and all claification';
      await userEvent.type(textArea, response);
      expect(screen.getByText(response)).toBeInTheDocument();
    });
  });

  testFileUploadSection(
    renderApp,
    () => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: `File exceeds total upload limit of 50MB!`,
      });
    },
    () => server.use(uploadDocumentSuccess()),
  );

  describe('ReplyNeedsClarificationSubmitActions', () => {
    test('should call document upload api on submit action and trigger show workflow status', async () => {
      server.use(submitClarificationSuccess());
      const response = 'uploaded new document with clear visibility and all claification';
      renderApp();
      await applyForm(response);
      expect(fetchWorkflowSpy).toHaveBeenCalledTimes(2);
    });

    test('should call document upload api on submit action and show error incase api fails', async () => {
      server.use(submitClarificationError());
      const response = 'uploaded new document';
      renderApp();
      await applyForm(response);
      expect(showNotificationSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
        }),
      );
    });
  });

  test('should close modal on close CTA', async () => {
    renderApp();
    const closeCTA = screen.getByRole('button', {
      name: 'Cancel',
    });
    await userEvent.click(closeCTA);
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
