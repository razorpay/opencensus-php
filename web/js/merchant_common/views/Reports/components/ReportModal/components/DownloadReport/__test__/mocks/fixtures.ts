import { getOverViewStateWith } from 'merchant_common/views/Reports/features/Overview/__test__/fixtures';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';
import { preDefinedDurations } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/data';

export const initialState = getOverViewStateWith({
  allConfigs: {
    loading: false,
    error: false,
    data: mockConfigs,
  },
});

// choosing a market place config type for testing complete flow
export const TEST_CONFIG = mockConfigs[0];
export const TEST_USER = {
  name: 'Rzp',
  email: 'rzp@gmail.com',
  id: 'MID_ASDASW',
  current: 'IASD_ASD',
  isOrgAllowedFunctionality: jest.fn(() => true),
  findTag: jest.fn(),
  isMarketplaceEnabled: true,
};
export const TEST_DUM_ACCOUNT = {
  name: `RZP`,
  id: 'DUMMY_ACCOUNT',
  email: 'dummy-account@rzp.com',
  tag: 'Ref Account',
  current: true,
};
export const TEST_ACCOUNTS_STATE = {
  accounts: [],
  count: 0,
  loading: false,
  error: false,
};

// Download api params that will be checked against the mock api.
export const DEFAULT_DOWNLOAD_API_PARAMS = {
  generatedBy: TEST_USER.current ?? TEST_USER.id,
  headers: {},
  payload: {
    config_id: TEST_CONFIG.id,
    emails: ['rzp@rzp.com'],
    start_time: preDefinedDurations[0].value.startDate.clone().unix(),
    end_time: preDefinedDurations[0].value.endDate.clone().unix(),
    template_overrides: {
      file_meta: { extension: 'csv', filename: 'Rzp Doc', delimiter: ',' },
    },
  },
  accountId: undefined,
};

export const BATCH_TITLE = 'Which details should be part of this report?';
export const DATE_HELP_TEXT = 'Period of data, time, date etc.';

export const BATCH_PAGE = {
  HELP_TEXT: 'Select the page for which you want to download the data.',
  ERROR_TEXT: 'Mandatory Field: Select the page for which you want to download the data.',
  LABEL: 'Select Batch Payment Page',
  PLACEHOLDER: 'Batch Page ID',
};

export const BATCH_ID = {
  HELP_TEXT:
    'Select one or more batch IDs from the dropdown or type the batch ID to search and select.',
  ERROR_TEXT:
    'Mandatory Field: Select one or more batch IDs from the dropdown or type the batch ID to search and select.',
  LABEL: 'Select Batch',
  PLACEHOLDER: 'Please enter any batch ID you want to search and select it from the list.',
};

export const PAYMENT_STATUS = {
  HELP_TEXT: 'Filter the report results by selecting the payment status.',
  ERROR_TEXT: 'Mandatory Field: Filter the report results by selecting the payment status.',
  LABEL: 'Select Payment Status',
  PLACEHOLDER: 'Select payment status from the list.',
};
