import { testBottomLinks, testFileUploadSection } from './mocks/fixtures/common';
import {
  applyForm,
  testBasedOnWorkflowTypes,
  workflowData,
} from './mocks/fixtures/NeedsClarification';
import * as fetchWorkflow from 'merchant/reducers/workflows';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import * as TrackEvent from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import {
  fetchWorkflowStatusError,
  fetchWorkflowStatusSuccess,
  submitClarificationError,
  submitClarificationSuccess,
  uploadDocumentSuccess,
} from './mocks/handlers';

describe('NeedsClarificationFlow', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  const fetchWorkflowSpy = jest.spyOn(fetchWorkflow, 'fetchWorkflowStatus');
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');
  const trackEventsSpy = jest.spyOn(TrackEvent, 'trackIEEvent');

  const renderApp = ({ props, bankWorkflows } = {}) => {
    const workflowType = props?.workflowType || WORKFLOW_TYPES.BANK_DETAIL_UPDATE;
    return render(
      <BankAccountUpdateFlow
        defaultView={BANK_ACCOUNT_UPDATE_STEPS.NEEDS_CLARIFICATION}
        workflowType={workflowType}
        workflowName="Change your bank account"
        {...props}
      />,
      {
        initialState: {
          workflows: {
            [workflowType]: {
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
    trackEventsSpy.mockClear();
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

  testBasedOnWorkflowTypes(
    'should show no clarification required notification when workflow status is not open and approved',
    async (workflowType) => {
      server.use(
        fetchWorkflowStatusSuccess({
          ...workflowData,
          workflow_status: 'close',
        }),
      );
      renderApp({
        props: {
          workflowType,
        },
      });
      expect(fetchWorkflowSpy).toHaveBeenCalledTimes(1);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: `No clarification required for Change your bank account workflow`,
        });
      });
    },
  );

  testBasedOnWorkflowTypes(
    'should show already responded notification when workflow tags includes customer responded',
    async (workflowType) => {
      server.use(
        fetchWorkflowStatusSuccess({
          ...workflowData,
          tags: ['customer-responded'],
        }),
      );
      renderApp({
        props: {
          workflowType,
        },
      });
      expect(fetchWorkflowSpy).toHaveBeenCalledTimes(1);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: `You've already responded to Change your bank account workflow`,
        });
      });
    },
  );

  testBasedOnWorkflowTypes(
    'should show error notification when fetch workflow api fails',
    async (workflowType) => {
      server.use(fetchWorkflowStatusError());
      renderApp({
        props: {
          workflowType,
        },
      });
      expect(fetchWorkflowSpy).toHaveBeenCalledTimes(1);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: `Something went wrong, Please try again later`,
        });
      });
    },
  );

  testBottomLinks(renderApp, 'Submit details');

  testBasedOnWorkflowTypes('should call trackevent on file drag', async (workflowType) => {
    renderApp({
      props: {
        workflowType,
      },
    });
    const fileUpload = screen.getByRole('button', {
      name: 'File upload drop',
    });
    await userEvent.click(fileUpload);
    if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      expect(trackEventsSpy).toHaveBeenCalledWith({
        objectName: 'File',
        actionName: 'Dragged and Dropped',
      });
    }
  });

  describe('ReplyNeedsClarificationSubmitActions', () => {
    testBasedOnWorkflowTypes(
      'should call document upload api on submit action and trigger show workflow status',
      async (workflowType) => {
        server.use(submitClarificationSuccess());
        const response = 'uploaded new document with clear visibility and all claification';
        renderApp({
          props: {
            workflowType,
          },
        });
        await applyForm(response);
        await waitFor(() => {
          expect(fetchWorkflowSpy).toHaveBeenCalledTimes(2);
        });
      },
    );

    testBasedOnWorkflowTypes(
      'should call document upload api on submit action and show error incase api fails',
      async (workflowType) => {
        server.use(submitClarificationError());
        const response = 'uploaded new document';
        renderApp({
          props: {
            workflowType,
          },
        });
        await applyForm(response);
        expect(showNotificationSpy).toHaveBeenCalledWith(
          expect.objectContaining({
            type: 'error',
          }),
        );
      },
    );
  });

  describe('TextArea', () => {
    test('should render text area for reply response', () => {
      renderApp();
      expect(screen.getByText('Note')).toBeInTheDocument();
      expect(screen.getByText('From Razorpay support')).toBeInTheDocument();
    });

    testBasedOnWorkflowTypes(
      'should add note on change action in textarea',
      async (workflowType) => {
        renderApp({
          props: {
            workflowType,
          },
        });
        const textArea = screen.getByRole('textbox');
        const response = 'uploaded new document with clear visibility and all claification';
        await userEvent.type(textArea, response);
        expect(screen.getByText(response)).toBeInTheDocument();
      },
    );

    test('should render multiple upload documents title when multiple upload enabled', () => {
      renderApp({
        props: {
          isMultiple: true,
        },
      });
      expect(screen.getByText('You can upload multiple documents below')).toBeInTheDocument();
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

  testBasedOnWorkflowTypes('should close modal on close CTA', async (workflowType) => {
    renderApp({
      props: {
        workflowType,
      },
    });
    const closeCTA = screen.getByRole('button', {
      name: 'Cancel',
    });
    await userEvent.click(closeCTA);
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
