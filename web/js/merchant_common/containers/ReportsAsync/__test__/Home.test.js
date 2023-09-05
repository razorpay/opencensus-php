import { server, userEvent, waitFor, screen } from 'test-utils';
import {
  defaultState,
  getDefaultUserObj,
  renderApp,
} from 'merchant_common/containers/ReportsAsync/__test__/mocks/fixtures/Home';
import {
  configs,
  logs,
  linkedAccounts,
} from 'merchant_common/containers/ReportsAsync/__test__/mocks/fixtures/data';
import * as analytics from 'common/utils/analytics';
import { rest } from 'msw';

describe('Reports Home Container', () => {
  const createLog = jest.fn();
  const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

  beforeEach(() => {
    analyticsTrackSpy.mockClear();
    createLog.mockClear();
  });

  const clickGenerateReportButton = async (response = { success: true, data: logs[0] }) => {
    server.use(
      // create log
      rest.post('*/merchant/api/*/reporting/logs', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            ...response,
          }),
          ctx.delay(50),
        );
      }),
    );

    const renderAppUtils = renderApp();
    let generateReportButton;
    await waitFor(() => {
      generateReportButton = screen.getByRole('button', { name: 'Generate Report' });
      expect(generateReportButton).toBeInTheDocument();
    });
    await userEvent.click(generateReportButton);
    return renderAppUtils;
  };

  // TODO: fix this test case
  test.skip('should render dashboard banner, test mode banner and loader', async () => {
    renderApp();
    expect(screen.queryByText('Zapier Banner')).not.toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    expect(screen.getByText('Easter Egg')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Reports' })).toBeInTheDocument();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
    expect(screen.queryByText('Log list component')).not.toBeInTheDocument();

    await waitFor(() => {
      expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
      expect(screen.getByText('Log list component')).toBeInTheDocument();
      expect(screen.getByText(`${logs.length} Logs found`)).toBeInTheDocument();
      expect(screen.getByText('1 Custom Configs Found')).toBeInTheDocument();
    });
  });

  test('should render zappier banner when isPartOfZapierIntegrationExperiment is true', () => {
    window.rzp_user = {
      splitz_experiments: { HpQN5BGbQ793ir: { variables: { result: 'on' } } },
    };
    renderApp();
    expect(screen.getByText('Zapier Banner')).toBeInTheDocument();
    window.rzp_user = null;
  });

  test('should call fetchAccounts when showSelectAccount is true', async () => {
    const defaultAccountName = 'default account name';
    renderApp({
      initialState: {
        session: {
          user: getDefaultUserObj({
            features: [{ feature: 'marketplace' }],
            transaction_report_email: 'test+1@razorpay.com',
            name: undefined,
            user: { name: defaultAccountName },
          }),
        },
      },
    });
    await waitFor(() => {
      expect(screen.getByText(`${linkedAccounts.length} Accounts found`)).toBeInTheDocument();
    });
    expect(screen.getByText(`Default account name - ${defaultAccountName}`)).toBeInTheDocument();
  });

  test('should have 4 custom configs if user has relevant tags and allowed monthlyInvoice functionality', async () => {
    renderApp({
      initialState: {
        session: {
          user: getDefaultUserObj({ tags: ['borking_report', 'rpp_report', 'dsp_report'] }),
        },
      },
    });
    await waitFor(() => {
      expect(screen.getByText(`4 Custom Configs Found`)).toBeInTheDocument();
    });
  });

  test('should call hj function if it exists', () => {
    window.hj = jest.fn();
    renderApp();
    expect(window.hj).toHaveBeenCalledWith('trigger', 'report-async-started');
    expect(window.hj).toHaveBeenCalledWith('tagRecording', [
      'report-async-started',
      defaultState.session.user.current,
    ]);
  });

  test('should call onLoadMoreLogs function on clicking load more button', async () => {
    renderApp();
    const loadMoreButton = await screen.findByRole('button', { name: 'Load More Logs' });
    expect(loadMoreButton).toBeInTheDocument();
    await userEvent.click(loadMoreButton);
    expect(analyticsTrackSpy).toHaveBeenCalled();
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'load more',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
      },
    });
  });

  test.skip('should call generateReport function and analyticsTrack with Success on clicking generate report button', async () => {
    await clickGenerateReportButton();
    await waitFor(() => {
      expect(analyticsTrackSpy).toHaveBeenCalled();
    });
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'generate report',
      actionName: 'result',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: configs[0].name,
        periodStart: undefined,
        periodEnd: undefined,
        formatSelected: undefined,
        emailSelected: false,
        status: 'Success',
      },
    });
  });

  test.skip('should show same report type notification and call analyticsTrack with same report type on clicking generate report button with already shown log', async () => {
    await clickGenerateReportButton({
      success: true,
      data: { ...logs[0], is_already_present: true },
    });
    await waitFor(() => {
      const infoMessage =
        'Request with same report type and date range is in processing. Please check your request history';
      expect(screen.getByText(infoMessage)).toBeInTheDocument();
      expect(analyticsTrackSpy).toHaveBeenCalled();
      expect(analyticsTrackSpy).toHaveBeenCalledWith({
        objectName: 'generate report',
        actionName: 'result',
        screen: 'reports',
        properties: {
          location: 'generate reports',
          reportType: configs[0].name,
          periodStart: undefined,
          periodEnd: undefined,
          formatSelected: undefined,
          emailSelected: false,
          status: 'Success',
          infoMessage,
        },
      });
    });
  });

  test.skip('should show call pollLog using accountId when report generated by and consumer are different on clicking generate report button', async () => {
    await clickGenerateReportButton({ success: true, data: { ...logs[0], generated_by: 'test' } });
    await waitFor(() => {
      expect(analyticsTrackSpy).toHaveBeenCalled();
      expect(analyticsTrackSpy).toHaveBeenCalledWith({
        objectName: 'generate report',
        actionName: 'result',
        screen: 'reports',
        properties: {
          location: 'generate reports',
          reportType: configs[0].name,
          periodStart: undefined,
          periodEnd: undefined,
          formatSelected: undefined,
          emailSelected: false,
          status: 'Success',
        },
      });
    });
  });

  test('should show you reached maximum limit error on clicking generate report button', async () => {
    const error = 'You reached maximum limit';
    const { container } = await clickGenerateReportButton({ success: false, errors: [error] });
    await waitFor(() => {
      // screen.getByText(error) is not working here
      expect(
        container.querySelector('.Notification.Notification--error.Notification__show'),
      ).toHaveTextContent(`${error} See more details on this error`);
    });
  });

  test('should show error and call analyticsTrack with error on clicking generate report button', async () => {
    const error = 'something failed';
    await clickGenerateReportButton({ success: false, errors: [error] });
    await waitFor(() => {
      expect(screen.getByText(error)).toBeInTheDocument();
    });
    expect(analyticsTrackSpy).toHaveBeenCalled();
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'generate report',
      actionName: 'result',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        status: 'Failure',
        failureReason: error,
      },
    });
  });
});
