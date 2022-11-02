import { render, screen, fireEvent, delay, waitFor } from 'common/services/test/test-utils';
import GenerateReportPanel from 'merchant_common/containers/ReportsAsync/GenerateReportPanel';
import * as analytics from 'common/utils/analytics';
import * as selfServeAnalytics from 'common/utils/selfServeAnalytics';
import { DEFAULT_PERIOD_OPTIONS } from 'merchant/views/Capital/CashAdvance/constants';
import { getStartAndEndUnixTimeStampsForDaysFrom } from 'merchant_common/containers/ReportsAsync/utils';

const reportsOption = {
  id: 'reports',
  name: 'Reports',
  description: 'This report provides a list of the settlement(s) in selected time range',
  type: 'reports',
};

const settlementOption = {
  id: 'settlements',
  name: 'Settlements',
  description:
    'This report provides a list of the settlement(s) in selected time range. It does not include details of the transactions that were settled. Details include settlement ID, date, UTR, and others.',
  type: 'settlements',
};

const paymentLinksOption = {
  id: 'paymentlinks',
  name: 'Payment Links',
  type: 'paymentlinksv2',
};

const configForAggregratedReport = {
  id: 'aggregatedAccounts',
  name: 'Aggregated Accounts',
  template: {
    referred_accounts: 'all',
  },
};

const customConfig = {
  id: 'custom',
  name: 'Custom Config',
  type: 'custom',
};

const accounts = [
  {
    email: 'account+1@razorpay.com',
    id: 'acc_TEST_ACCOUNT_1',
    name: 'Account 1',
  },
  {
    email: 'account+2@razorpay.com',
    id: 'acc_TEST_ACCOUNT_2',
    name: 'Account 2',
    current: true,
  },
];

const emailReportOptions = ['email+1@razorpay.com'];

describe('GenerateReportPanel', () => {
  const defaultProps = {
    configs: {
      loading: false,
      items: [
        reportsOption,
        settlementOption,
        configForAggregratedReport,
        customConfig,
        paymentLinksOption,
      ],
    },
    customConfigs: [],
    accounts: {
      accounts,
      loading: false,
    },
    emailReportOptions,
    showSelectAccount: true,
    onGenerateReport: jest.fn(),
    mode: 'test',
  };

  const App = (props = {}) => <GenerateReportPanel {...defaultProps} {...props} />;

  const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
  const selfServeTrackInitiateSpy = jest.spyOn(selfServeAnalytics, 'selfServeTrackInitiate');

  window.open = jest.fn();

  const selectConfig = async (option) => {
    fireEvent.click(document.getElementsByClassName('PowerSelect__TriggerLabel')[0]);
    // Delay is added to prevent onFocus error of react-power-select library - react-power-select needs to be upgraded
    await delay(10);
    fireEvent.click(screen.getByText(option.name));
  };

  const getGenerateReportPayload = (configOption, emails) => {
    // since default value is yesterday
    const [periodStart, periodEnd] = getStartAndEndUnixTimeStampsForDaysFrom(0);
    return {
      config_id: configOption.id,
      start_time: periodStart,
      end_time: periodEnd,
      emails,
    };
  };

  beforeEach(() => {
    analyticsTrackSpy.mockClear();
  });

  test('should render Generate Report Panel component', () => {
    render(<App />);
    expect(
      screen.getByText(
        'You can generate new reports or download from the list of recently generated reports',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Select Report Type')).toBeInTheDocument();
    expect(screen.getByText('Select Format')).toBeInTheDocument();
  });

  test('should show spinner on config loading', () => {
    render(<App configs={{ loading: true, items: [] }} />);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should call onConfigChange on selecting config', async () => {
    render(<App />);
    await selectConfig(settlementOption);
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'select report type',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: settlementOption.name,
      },
    });

    expect(selfServeTrackInitiateSpy).toHaveBeenCalledWith({
      selfServeAction: 'Report Generated',
      page: 'Reports',
      screen: 'Reports',
    });
  });

  test('should show accounts loading state', async () => {
    render(<App accounts={{ loading: true, accounts: [] }} />);
    await selectConfig(settlementOption);
    expect(screen.getByText(/loading accounts/i)).toBeInTheDocument();
  });

  test('should use first account as default account and onAccountChange on selecting account', async () => {
    const { container } = render(<App />);
    await selectConfig(settlementOption);
    const selectAccountElement = screen.getByPlaceholderText(
      'Account ID, Account Name, Email Address',
    );
    expect(selectAccountElement).toHaveValue(accounts[0].name);
    fireEvent.click(selectAccountElement);
    fireEvent.click(screen.getByText(accounts[1].name));
    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'account selection',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: accounts[1],
      },
    });
    expect(container.getElementsByClassName('account-id')[0]).toHaveTextContent(accounts[1].id);
  });

  describe('For validating dates', () => {
    beforeAll(() => {
      jest.useFakeTimers('modern').setSystemTime(new Date('2022-10-10'));
    });

    afterAll(() => {
      jest.useRealTimers();
    });

    const openDatePicker = (elementName) => {
      const selectDateElement = screen.getByRole('textbox', { name: elementName });
      fireEvent.click(selectDateElement);
      return selectDateElement;
    };

    const selectDate = (elementName, dateToBeSelected, idx) => {
      openDatePicker(elementName);

      // getByTitle for selecting date is not working as expected
      fireEvent.click(screen.getAllByTitle(dateToBeSelected)[idx]);
    };

    const selectPeriod = (period) => {
      const selectPeriodElement = screen.getByRole('combobox', { name: 'Select Period' });

      fireEvent.change(selectPeriodElement, { target: { value: period.name } });
    };

    test.skip('should render start at date error when end date is greater than start date', async () => {
      render(<App />);
      await selectConfig(settlementOption);
      selectPeriod(DEFAULT_PERIOD_OPTIONS[7]);
      selectDate('Start At', 'October 5, 2022', 0);
      // end at is the 2nd element as startDate's calendar is not closing.
      selectDate('End At', 'October 4, 2022', 1);
      await waitFor(() => {
        expect(screen.getByTestId('date-range-error-message')).toHaveTextContent(
          "Start at date can't exceed end at date",
        );
        expect(screen.getByRole('button', { name: 'Generate Report' })).toBeDisabled();
      });
    });

    test.skip('should render 31 days message when range difference is more than 31 days', async () => {
      render(<App />);

      await selectConfig(configForAggregratedReport);
      selectPeriod(DEFAULT_PERIOD_OPTIONS[7]);

      // change the date to make the difference > 31
      openDatePicker('Start At');
      fireEvent.click(screen.getByTitle('Previous month (PageUp)'));
      selectDate('Start At', 'September 1, 2022', 0);
      selectDate('End At', 'October 4, 2022', 0);

      await waitFor(() => {
        expect(screen.getByTestId('date-range-error-message')).toHaveTextContent(
          'You can only select up to 31 days for this report',
        );
        expect(screen.getByRole('button', { name: 'Generate Report' })).toBeDisabled();
      });
    });
  });

  test('should show Download Report on custom config', async () => {
    render(<App />);
    await selectConfig(customConfig);
    expect(screen.getByRole('button', { name: 'Download Report' })).toBeInTheDocument();
  });

  test('should generate report be disabled when email report options is empty', async () => {
    render(<App emailReportOptions={[]} />);
    await selectConfig(settlementOption);
    expect(screen.getByRole('button', { name: 'Generate Report' })).toBeDisabled();
  });

  test('should call onGenerateReport on clicking generate report', async () => {
    render(<App />);

    await selectConfig(settlementOption);
    fireEvent.click(screen.getByRole('checkbox', { name: emailReportOptions[0] }));
    const generateReportButton = screen.getByRole('button', { name: 'Generate Report' });
    fireEvent.click(generateReportButton);
    const [periodStart, periodEnd] = getStartAndEndUnixTimeStampsForDaysFrom(0);

    expect(selfServeTrackInitiateSpy).toHaveBeenCalledWith({
      selfServeAction: 'Report Downloaded',
      page: 'Reports',
      screen: 'Reports',
    });

    expect(analyticsTrackSpy).toHaveBeenLastCalledWith({
      objectName: 'generate report',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: settlementOption.name,
        accountSelected: accounts[0],
        periodStart,
        periodEnd,
        formatSelected: {},
        emailSelected: true,
      },
    });

    const payload = getGenerateReportPayload(settlementOption, emailReportOptions);

    expect(defaultProps.onGenerateReport).toHaveBeenCalledWith(payload, accounts[0].id);

    // uncheck email
    fireEvent.click(screen.getByRole('checkbox', { name: emailReportOptions[0] }));
    fireEvent.click(generateReportButton);
    payload.emails = undefined;
    expect(defaultProps.onGenerateReport).toHaveBeenCalledWith(payload, accounts[0].id);

    await selectConfig(customConfig);
    fireEvent.click(screen.getByRole('button', { name: 'Download Report' }));
    expect(window.open).toHaveBeenCalled();
  });

  test('should generate payload of paymentsLinkv2 when paymentLinksOption is selected', async () => {
    render(<App />);
    await selectConfig(paymentLinksOption);
    fireEvent.click(screen.getByRole('button', { name: 'Generate Report' }));

    const payload = {
      ...getGenerateReportPayload(paymentLinksOption),
      template_overrides: {
        filters: {
          paymentlinksv2: {
            mode: {
              op: 'IN',
              values: [defaultProps.mode],
            },
          },
        },
        file_meta: {
          extension: 'csv',
        },
      },
    };

    expect(defaultProps.onGenerateReport).toHaveBeenLastCalledWith(payload, false);
  });

  test("should account info be empty when there're no accounts", async () => {
    render(<App accounts={{ accounts: [], loading: false }} />);
    await selectConfig(settlementOption);
    fireEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
    const payload = getGenerateReportPayload(settlementOption);
    expect(defaultProps.onGenerateReport).toHaveBeenLastCalledWith(payload, undefined);
  });

  test("should account info be empty when there're no accounts", async () => {
    render(<App accounts={{ accounts: [], loading: false }} />);
    await selectConfig(settlementOption);
    fireEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
    const payload = getGenerateReportPayload(settlementOption);
    expect(defaultProps.onGenerateReport).toHaveBeenLastCalledWith(payload, undefined);
  });

  test('should account info be empty when the account is current', async () => {
    // for current account type
    render(<App accounts={{ accounts: [accounts[1]], loading: false }} />);
    await selectConfig(settlementOption);
    fireEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
    const payload = getGenerateReportPayload(settlementOption);
    expect(defaultProps.onGenerateReport).toHaveBeenLastCalledWith(payload, undefined);
  });
});
