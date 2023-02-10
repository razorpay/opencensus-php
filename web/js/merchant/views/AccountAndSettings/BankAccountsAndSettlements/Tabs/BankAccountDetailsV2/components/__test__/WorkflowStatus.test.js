import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';
import { renderApp } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/__test__/mocks/fixtures/WorkflowStatus';
import { workflowSuccess } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/__test__/mocks/handlers';
import moment from 'moment';
import { delay, screen, server, waitFor } from 'test-utils';

describe('WorkflowStatus', () => {
  const defaultWorkflowStatus = {
    workflow_status: 'open',
    needs_clarification: false,
    request_under_validation: true,
    tags: [],
    workflow_created_at: moment().subtract(7, 'days').unix(),
  };

  test('should render workflow banner when workflow is executed', async () => {
    server.use(
      workflowSuccess({
        ...defaultWorkflowStatus,
        workflow_status: 'executed',
        request_under_validation: false,
      }),
    );
    renderApp();
    await waitFor(() => expect(screen.getByText(BannerType.SUCCESS)).toBeInTheDocument());
  });

  describe('When settlement is active', () => {
    test('should not render workflow banner when workflow is closed', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_status: 'closed',
          request_under_validation: false,
        }),
      );
      renderApp();
      // wait for the fetchWorkflowStatus
      await delay();
      expect(screen.queryByText('Banner')).not.toBeInTheDocument();
    });

    test('should render workflow banner when workflow is rejected', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_status: 'rejected',
          request_under_validation: false,
        }),
      );
      renderApp();
      await waitFor(() =>
        expect(screen.getByText(BannerType.ACTIVE_SETTLEMENT_REJECTED)).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow has customer-responded status', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          needs_clarification: true,
          request_under_validation: false,
          tags: ['customer-responded'],
        }),
      );
      renderApp();
      await waitFor(() =>
        expect(
          screen.getByText(BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN),
        ).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow has awaiting-customer-response status', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          needs_clarification: true,
          request_under_validation: false,
          tags: ['awaiting-customer-response'],
        }),
      );
      renderApp();
      await waitFor(() =>
        expect(screen.getByText(BannerType.ACTIVE_SETTLEMENT_NC)).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow is under review time breached again', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
        }),
      );
      renderApp();
      await waitFor(() =>
        expect(
          screen.getByText(BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN),
        ).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow is under review', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_created_at: moment().unix(),
        }),
      );
      renderApp();
      await waitFor(() =>
        expect(screen.getByText(BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW)).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow is under review time breached', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_created_at: moment().subtract('3', 'days').unix(),
        }),
      );
      renderApp();
      await waitFor(() =>
        expect(
          screen.getByText(BannerType.ACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED),
        ).toBeInTheDocument(),
      );
    });
  });

  describe('When settlement is on hold', () => {
    test('should not render workflow banner when workflow is closed', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_status: 'closed',
          request_under_validation: false,
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      // wait for the fetchWorkflowStatus
      await delay();
      expect(screen.queryByText('Banner')).not.toBeInTheDocument();
    });

    test('should render workflow banner when workflow is rejected', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_status: 'rejected',
          request_under_validation: false,
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      await waitFor(() =>
        expect(screen.getByText(BannerType.INACTIVE_SETTLEMENT_REJECTED)).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow has customer-responded status', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          needs_clarification: true,
          request_under_validation: false,
          tags: ['customer-responded'],
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      await waitFor(() =>
        expect(
          screen.getByText(BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN),
        ).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow has awaiting-customer-response status', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          needs_clarification: true,
          request_under_validation: false,
          tags: ['awaiting-customer-response'],
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      await waitFor(() =>
        expect(screen.getByText(BannerType.INACTIVE_SETTLEMENT_NC)).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow is under review time breached again', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      await waitFor(() =>
        expect(
          screen.getByText(BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED_AGAIN),
        ).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow is under review', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_created_at: moment().unix(),
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      await waitFor(() =>
        expect(screen.getByText(BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW)).toBeInTheDocument(),
      );
    });

    test('should render workflow banner when workflow is under review time breached', async () => {
      server.use(
        workflowSuccess({
          ...defaultWorkflowStatus,
          workflow_created_at: moment().subtract('3', 'days').unix(),
        }),
      );
      renderApp({
        props: {
          isSettlementOnHold: true,
        },
      });
      await waitFor(() =>
        expect(
          screen.getByText(BannerType.INACTIVE_SETTLEMENT_UNDER_REVIEW_TIME_BREACHED),
        ).toBeInTheDocument(),
      );
    });
  });
});
