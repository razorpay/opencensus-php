import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { ReportModal } from 'merchant_common/views/Reports/components';
import * as modalFn from 'merchant_common/reducers/modals';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import { getOverViewStateWith } from 'merchant_common/views/Reports/features/Overview/__test__/fixtures';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';
import * as notification from 'merchant_common/reducers/notifications';
import {
  MANDATORY_FIELD_REQUIRED,
  REPORT_GENERATE_LOG_POST_SUCCESS,
} from 'merchant_common/views/Reports/constants/notifications';
import * as downloadAPI from 'merchant_common/views/Reports/api/downloadModal';
import { mockLogs } from 'merchant_common/views/Reports/redux/__test__/fixtures/logs.fixture';
import { preDefinedDurations } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/data';
import * as accountsApi from 'merchant/reducers/marketplace/accounts';

const initialState = getOverViewStateWith({
  allConfigs: {
    loading: false,
    error: false,
    data: mockConfigs,
  },
});

// choosing a market place config type for testing complete flow
const TEST_CONFIG = mockConfigs[0];
const TEST_USER = {
  name: 'Rzp',
  email: 'rzp@gmail.com',
  id: 'MID_ASDASW',
  current: 'IASD_ASD',
  isOrgAllowedFunctionality: jest.fn(() => true),
  findTag: jest.fn(),
  isMarketplaceEnabled: true,
};
const TEST_DUM_ACCOUNT = {
  name: `RZP`,
  id: 'DUMMY_ACCOUNT',
  email: 'dummy-account@rzp.com',
  tag: 'Ref Account',
  current: true,
};
const TEST_ACCOUNTS_STATE = {
  accounts: [],
  count: 0,
  loading: false,
  error: false,
};

jest.mock('common/utils/debounce', () => (fn?) => (query) => {
  if (query.length) {
    fn(query);
  }
});
jest.spyOn(notification, 'showNotification');
jest.spyOn(downloadAPI, 'downloadNewReport').mockImplementation(
  () =>
    new Promise((res) =>
      res({
        success: true,
        data: {
          id: mockLogs[0].id,
        },
      }),
    ),
);
jest.spyOn(accountsApi, 'fetchAccountsApi').mockImplementation(() =>
  new Promise<{
    json: () => {
      data: { items: { name: string; id: string; email: string; tag: string; current: boolean }[] };
    };
  }>((res) =>
    res({
      json: () => ({
        data: {
          items: [TEST_DUM_ACCOUNT],
        },
      }),
    }),
  ).then((data) => data.json()),
);
jest.mock('merchant_common/views/Reports/utils/commonUtils', () => ({
  ...(jest.requireActual('merchant_common/views/Reports/utils/commonUtils') as Record<
    string,
    unknown
  >),
  getAvailableEmails: () => ['joel.jaimon@gmail.com', 'unactivated@gmail.com', 'rzp@rzp.com'],
}));
jest.spyOn(modalFn, 'closeModal');

describe('Download Custom Reports', () => {
  beforeAll(() => {});

  const App = ({ configId }: { configId?: string }): JSX.Element => {
    return (
      <ReportModal
        dashboardType={REPORT_TEST_DASHBOARD}
        type="download_report"
        params={{
          selectedConfig: configId,
        }}
      />
    );
  };
  const renderDetailedApp = () =>
    render(<App configId={TEST_CONFIG.id} />, {
      initialState: {
        ...initialState,
        accounts: TEST_ACCOUNTS_STATE,
        session: {
          user: TEST_USER,
          mode: 'live',
        },
      },
    });

  test('should render modal without error', () => {
    render(<App />);
    expect(screen.getByText('Download report for your business')).toBeInTheDocument();
    expect(
      screen.getByText(
        `A new improved version of reports now available for you to download. You can now select the specific date and time period for which you would like to see the report.`,
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText('What report is this?')).toBeInTheDocument();
    expect(screen.queryByText('What will you receive in this report?')).toBeInTheDocument();
    expect(screen.queryByText('Do you want this report in an email?')).toBeInTheDocument();
    expect(screen.queryByLabelText('Close Modal')).toBeInTheDocument();
    expect(screen.getByLabelText('Start Download')).toBeInTheDocument();
  });

  test('should open 1st section by default', () => {
    render(<App />);
    expect(screen.getByPlaceholderText('Select A Report')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Eg: Monthly Recon Report')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Excel or CSV')).toBeInTheDocument();
    expect(
      screen.queryByPlaceholderText('Select duration covered in each report'),
    ).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Custom Switch')).not.toBeInTheDocument();
  });

  test('should throw error on start download click when the mandatory fields are not filled', async () => {
    renderDetailedApp();
    await userEvent.click(screen.getByLabelText('Start Download'));
    expect(notification.showNotification).toHaveBeenCalledWith({
      message: MANDATORY_FIELD_REQUIRED,
      type: 'error',
    });
  });

  test('should be able to fill data', async () => {
    renderDetailedApp();
    await userEvent.type(screen.getByPlaceholderText('Eg: Monthly Recon Report'), 'Rzp Doc');
    await userEvent.click(screen.getByPlaceholderText('Excel or CSV'));
    await userEvent.click(screen.getByTestId('CSV'));
    await userEvent.click(screen.getByLabelText('Select Account Field'));
    await userEvent.type(
      screen.getByLabelText('Search An Item Here'),
      'just searching a dummy account',
    );
    await expect(accountsApi.fetchAccountsApi).toHaveReturned();

    await userEvent.click(screen.getByLabelText('RZP (DUMMY_ACCOUNT)'));
    await userEvent.click(screen.getByText('What will you receive in this report?'));
    await userEvent.click(screen.getByLabelText('Custom Switch'));
    await userEvent.click(screen.getByLabelText('Custom Switch'));
    await userEvent.click(screen.getByPlaceholderText('Select duration covered in each report'));
    await userEvent.click(screen.getByTestId(preDefinedDurations[0].label));
    await userEvent.click(screen.getByLabelText('Yes Switch'));
    await userEvent.click(screen.getByText('Do you want this report in an email?'));
    await userEvent.click(screen.getByLabelText('Add Recipient Field'));
    await userEvent.click(screen.getByLabelText('rzp@rzp.com'));

    await userEvent.click(screen.getByLabelText('Start Download'));

    expect(downloadAPI.downloadNewReport).toHaveBeenCalledWith({
      generatedBy: TEST_USER.current ?? TEST_USER.id,
      headers: {},
      payload: {
        config_id: TEST_CONFIG.id,
        emails: ['rzp@rzp.com'],
        start_time: preDefinedDurations[0].value.startDate.clone().unix(),
        end_time: preDefinedDurations[0].value.endDate.clone().unix(),
        template_overrides: {
          file_meta: {
            extension: 'csv',
            filename: 'Rzp Doc',
          },
        },
      },
      accountId: undefined,
    });

    await expect(downloadAPI.downloadNewReport).toHaveReturned();
    expect(notification.showNotification).toHaveBeenCalledWith({
      message: REPORT_GENERATE_LOG_POST_SUCCESS,
      type: 'success',
    });
    expect(modalFn.closeModal).toHaveBeenCalled();
  });
});
