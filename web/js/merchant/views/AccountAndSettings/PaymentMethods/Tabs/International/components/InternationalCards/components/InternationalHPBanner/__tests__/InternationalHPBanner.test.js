import { defaultWorkflowDetails, testBanner } from './mocks/fixtures';
import { BannerType } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import InternationalHPBanner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/InternationalHPBanner/InternationalHPBanner';
import { delay, render, screen, server, waitFor } from 'test-utils';
import { fetchWorkflowDetails } from './mocks/handlers';

describe('WorkflowStatus', () => {
  const renderApp = ({ props, user } = {}) => {
    return render(<InternationalHPBanner {...props} />, {
      showModal: true,
      initialState: {
        session: {
          user: {
            ...user,
          },
        },
      },
    });
  };

  test('should render workflow homepage banner when workflow is executed', async () => {
    server.use(
      fetchWorkflowDetails({
        ...defaultWorkflowDetails,
        workflow_status: 'executed',
        request_under_validation: false,
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(BannerType.APPROVED)).toBeInTheDocument();
    });
    testBanner();
  });

  test('should render workflow homepage banner when workflow is executed', async () => {
    server.use(
      fetchWorkflowDetails({
        ...defaultWorkflowDetails,
        workflow_status: 'rejected',
        request_under_validation: false,
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(BannerType.REJECTED)).toBeInTheDocument();
    });
    testBanner();
  });

  test('should render workflow homepage banner when workflow is executed', async () => {
    server.use(
      fetchWorkflowDetails({
        ...defaultWorkflowDetails,
        needs_clarification: true,
        request_under_validation: false,
        tags: ['awaiting-customer-response'],
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(BannerType.NEEDS_CLARIFICATION)).toBeInTheDocument();
    });
    testBanner();
  });

  test('should not render workflow homepage banner when workflow is closed', async () => {
    server.use(
      fetchWorkflowDetails({
        ...defaultWorkflowDetails,
        workflow_status: 'closed',
        request_under_validation: false,
      }),
    );
    renderApp();
    // wait for the fetchWorkflowStatus action call
    await delay();
    expect(screen.queryByText('HomePage Banner')).not.toBeInTheDocument();
  });
});
