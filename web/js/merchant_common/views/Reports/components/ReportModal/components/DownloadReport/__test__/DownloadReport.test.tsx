import React from 'react';
import { findByText, getByText, queryByText, render, screen, server, userEvent } from 'test-utils';
import * as modalFn from 'merchant_common/reducers/modals';
import { REPORT_TEST_DASHBOARD, TODAY } from 'merchant_common/views/Reports/constants';
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
import { defineMatchMedia } from 'merchant_common/views/Reports/components/DateTimeRangePicker/__test__/fixtures';
import {
  DEFAULT_FORMATS,
  DELIMITER_SUPPORT_MAP,
  FORMATS_PLACEHOLDER,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';
import { getFormattedDate } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';
import {
  BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
  BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
  CONFIG_TYPE_BATCH_PAGES,
  PAYMENT_STATUS_OPTIONS,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/constants';
import {
  BATCH_ID_OPTIONS,
  BATCH_PAGE_ITEMS,
  getBatchIds,
  getPaymentPagesFileUploadPages,
} from './mocks/handlers';
import DownloadReport from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport';
import {
  BATCH_ID,
  BATCH_PAGE,
  BATCH_TITLE,
  DATE_HELP_TEXT,
  DEFAULT_DOWNLOAD_API_PARAMS,
  PAYMENT_STATUS,
  TEST_ACCOUNTS_STATE,
  TEST_CONFIG,
  TEST_DUM_ACCOUNT,
  TEST_USER,
  initialState,
} from './mocks/fixtures';

defineMatchMedia(false);

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
jest.setTimeout(35000);

describe('Download Reports', () => {
  beforeAll(() => {});

  const App = ({ configId }: { configId?: string }): JSX.Element => {
    return (
      <DownloadReport
        dashboardType={REPORT_TEST_DASHBOARD}
        params={{
          selectedConfig: configId,
        }}
      />
    );
  };

  const renderDetailedApp = ({ configId = TEST_CONFIG.id } = {}) =>
    render(<App configId={configId} />, {
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
    expect(screen.getByLabelText('Start Download')).toBeInTheDocument();
  });

  test('should open 1st section by default', () => {
    render(<App />);
    expect(screen.getByPlaceholderText('Select A Report')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Eg: Monthly Recon Report')).toBeInTheDocument();
    expect(screen.getByPlaceholderText(FORMATS_PLACEHOLDER)).toBeInTheDocument();
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

  // TODO
  test.skip('should be able to fill data', async () => {
    renderDetailedApp();
    await userEvent.type(screen.getByPlaceholderText('Eg: Monthly Recon Report'), 'Rzp Doc');
    await userEvent.click(screen.getByPlaceholderText('Excel, CSV or More'));
    await userEvent.click(screen.getByTestId('CSV'));
    await userEvent.click(screen.getByLabelText('Select Account Field'));
    await userEvent.type(
      screen.getByLabelText('Search An Item Here'),
      'just searching a dummy account',
    );

    expect(accountsApi.fetchAccountsApi).toHaveReturned();

    await userEvent.click(screen.getByLabelText('RZP (DUMMY_ACCOUNT)'));
    await userEvent.click(screen.getByText('What will you receive in this report?'));
    await userEvent.click(screen.getByPlaceholderText('Select duration covered in each report'));
    await userEvent.click(screen.getByTestId(preDefinedDurations[0].label));
    await userEvent.click(screen.getByLabelText('Yes Switch'));
    await userEvent.click(screen.getByText('Do you want this report in an email?'));
    await userEvent.click(screen.getByLabelText('Add Recipient Field'));
    await userEvent.click(screen.getByLabelText('rzp@rzp.com'));

    await userEvent.click(screen.getByLabelText('Start Download'));

    expect(downloadAPI.downloadNewReport).toHaveBeenCalledWith(DEFAULT_DOWNLOAD_API_PARAMS);

    expect(downloadAPI.downloadNewReport).toHaveReturned();
    expect(notification.showNotification).toHaveBeenCalledWith({
      message: REPORT_GENERATE_LOG_POST_SUCCESS,
      type: 'success',
    });
    expect(modalFn.closeModal).toHaveBeenCalled();
  });

  test.skip('should render helper text according to the date selected from the calendar', async () => {
    const todaysDate = TODAY.format('[Date is] DD MMMM YYYY');

    renderDetailedApp();

    const helpTextEl = screen.getByText(DATE_HELP_TEXT);

    await userEvent.click(screen.getByText('What will you receive in this report?'));

    // Enable custom date calendar.
    await userEvent.click(screen.getByLabelText('Custom Switch'));
    // Click to input field to open calendar.
    await userEvent.click(screen.getByLabelText('Picker Input Field'));
    // Select todays date as start date).
    await userEvent.click(screen.getByLabelText(todaysDate));
    // Select todays date as end date.
    await userEvent.click(screen.getByLabelText(todaysDate));
    // Disable the time format.
    await userEvent.click(screen.getByLabelText('Include Time Switch'));

    // Outside click.
    await userEvent.click(screen.getByLabelText('Custom Switch'));

    expect(helpTextEl).toHaveTextContent(
      getFormattedDate({ startDate: todaysDate, endDate: todaysDate }, false),
    );
  });

  test.skip('should be able to submit data with delimiter', async () => {
    const { label: formatLabel, value: formatValue } =
      DEFAULT_FORMATS.find(({ value }) => value === 'txt') || {};
    const { value: delimiterValue } = DELIMITER_SUPPORT_MAP[formatValue || '']?.[0] || {};

    renderDetailedApp();

    // First block
    // Click the formats input element to open dropdown.
    await userEvent.click(screen.getByPlaceholderText(FORMATS_PLACEHOLDER));
    // Select the format option from the dropdown.
    await userEvent.click(screen.getByTestId(formatLabel || ''));

    // Second block
    // Open the second block.
    await userEvent.click(screen.getByText('What will you receive in this report?'));
    // Click on the duration input field.
    await userEvent.click(screen.getByPlaceholderText('Select duration covered in each report'));
    // Select the option from the dropdown.
    await userEvent.click(screen.getByTestId(preDefinedDurations[0].label));

    // Start downloading.
    await userEvent.click(screen.getByLabelText('Start Download'));

    const updatedParams = {
      ...DEFAULT_DOWNLOAD_API_PARAMS,
      payload: {
        ...DEFAULT_DOWNLOAD_API_PARAMS.payload,
        emails: undefined,
        template_overrides: {
          file_meta: { extension: formatValue, delimiter: delimiterValue },
          filters: undefined,
        },
      },
    };

    expect(downloadAPI.downloadNewReport).toHaveBeenCalledWith(updatedParams);
  });

  test('should render batch payment block having three fields(batch page, batch id, payment status), when report of type - batch_pages and name - Bulk Payment Page Customers Report is selected', async () => {
    server.use(getPaymentPagesFileUploadPages(), getBatchIds());

    const config = mockConfigs.find(
      ({ type, name }) =>
        type === CONFIG_TYPE_BATCH_PAGES && name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
    );

    renderDetailedApp({ configId: config?.id });

    // Open the batch payment page block.
    await userEvent.click(screen.getByText(BATCH_TITLE));

    const batchPaymentPageFieldEl = screen.getByLabelText(BATCH_PAGE.LABEL);

    // Payment status field should be present.
    expect(screen.getByLabelText(PAYMENT_STATUS.LABEL)).toBeInTheDocument();
    // Batch page field should be present
    expect(getByText(batchPaymentPageFieldEl, BATCH_PAGE.PLACEHOLDER)).toBeInTheDocument();

    // When all option is selected batch id field is hidden.
    expect(screen.queryByText(BATCH_ID.LABEL)).not.toBeInTheDocument();

    await userEvent.click(batchPaymentPageFieldEl);
    // Type in batch payment page id.
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'test');

    // Select the item.
    await userEvent.click(await screen.findByLabelText(BATCH_PAGE_ITEMS[0].id));

    const batchIdField = screen.getByLabelText(BATCH_ID.LABEL);

    // Should have loading text.
    expect(screen.getByPlaceholderText('Loading... Please wait...')).toBeInTheDocument();

    // 'All' option should be pre-select for batch id field after getting batch ids options.
    expect(await findByText(batchIdField, 'All')).toBeInTheDocument();
  });

  test('should render batch payment block having only one field(batch page dropdown), when report of type - batch_pages and name - Bulk Payment Page Payments Report is selected and duration field should be hidden', async () => {
    server.use(getPaymentPagesFileUploadPages());

    const config = mockConfigs.find(
      ({ type, name }) =>
        type === CONFIG_TYPE_BATCH_PAGES && name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
    );

    renderDetailedApp({ configId: config?.id });

    // Open the batch page block.
    await userEvent.click(screen.getByText(BATCH_TITLE));

    // Payment status field should not be present.
    expect(screen.queryByLabelText(PAYMENT_STATUS.LABEL)).not.toBeInTheDocument();

    // Click on batch payment page field.
    await userEvent.click(screen.getByLabelText(BATCH_PAGE.LABEL));
    // Type in batch payment page id.
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'test');
    // Select the item.
    await userEvent.click(await screen.findByLabelText(BATCH_PAGE_ITEMS[0].id));

    // Even after selecting batch page, batch id field should be hidden.
    expect(screen.queryByText(BATCH_ID.LABEL)).not.toBeInTheDocument();
  });

  test('"All" option should not be present when batch ids are selected and also validate the field, when no batch ids are selected', async () => {
    server.use(getPaymentPagesFileUploadPages(), getBatchIds());

    const config = mockConfigs.find(
      ({ type, name }) =>
        type === CONFIG_TYPE_BATCH_PAGES && name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
    );

    renderDetailedApp({ configId: config?.id });

    // Open the batch page block.
    await userEvent.click(screen.getByText(BATCH_TITLE));
    // Click on batch page field.
    await userEvent.click(screen.getByLabelText(BATCH_PAGE.LABEL));
    // Type in batch page id.
    await userEvent.type(screen.getByLabelText('Search An Item Here'), 'test');
    // Select the item.
    await userEvent.click(await screen.findByLabelText(BATCH_PAGE_ITEMS[0].id));

    // Wait for loading to complete.
    const batchIdInputEl = await screen.findByPlaceholderText(BATCH_ID.PLACEHOLDER);

    // Open dropdown to select options.
    await userEvent.click(batchIdInputEl);
    // Click the option.
    await userEvent.click(screen.getByLabelText(BATCH_ID_OPTIONS[0]));

    // 'All' option should be unselected for batch id field.
    expect(queryByText(screen.getByLabelText(BATCH_ID.LABEL), 'All')).not.toBeInTheDocument();

    // Remove the selected batch id.
    await userEvent.click(
      screen.getByLabelText(`Remove Selected Option -> ${BATCH_ID_OPTIONS[0]}`),
    );
    // Start downloading.
    await userEvent.click(screen.getByLabelText('Start Download'));

    // Should render error msg when download button is clicked.
    expect(screen.getByText(BATCH_ID.ERROR_TEXT)).toBeInTheDocument();
  });

  test('"All" option should not be present when payment status are selected other than "All" option and also validate the field, when no payment status are selected', async () => {
    const config = mockConfigs.find(
      ({ type, name }) =>
        type === CONFIG_TYPE_BATCH_PAGES && name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
    );

    renderDetailedApp({ configId: config?.id });

    // Open the batch page block.
    await userEvent.click(screen.getByText(BATCH_TITLE));

    const paymentStatusFieldEl = screen.getByLabelText(PAYMENT_STATUS.LABEL);
    const selectedPaymentStatus = PAYMENT_STATUS_OPTIONS[1];

    // Should have all option pre-selected for payment status field.
    expect(getByText(paymentStatusFieldEl, 'All')).toBeInTheDocument();

    // Open dropdown to select option.
    await userEvent.click(screen.getByText(PAYMENT_STATUS.PLACEHOLDER));
    // Select the option.
    await userEvent.click(screen.getByLabelText(selectedPaymentStatus.label));

    // "All" option should not be present.
    expect(queryByText(paymentStatusFieldEl, 'All')).not.toBeInTheDocument();

    // Remove the selected payment status.
    await userEvent.click(
      screen.getByLabelText(`Remove Selected Option -> ${selectedPaymentStatus.label}`),
    );
    // Start downloading.
    await userEvent.click(screen.getByLabelText('Start Download'));

    // Should render error msg when download button is clicked.
    expect(screen.getByText(PAYMENT_STATUS.ERROR_TEXT)).toBeInTheDocument();
  });

  test('should validate batch page field', async () => {
    const config = mockConfigs.find(
      ({ type, name }) =>
        type === CONFIG_TYPE_BATCH_PAGES && name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
    );

    renderDetailedApp({ configId: config?.id });

    // Start downloading.
    await userEvent.click(screen.getByLabelText('Start Download'));

    expect(screen.getByText(BATCH_PAGE.ERROR_TEXT)).toBeInTheDocument();
  });
});
