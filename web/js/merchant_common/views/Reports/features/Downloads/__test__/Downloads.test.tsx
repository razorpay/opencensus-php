import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Downloads } from 'merchant_common/views/Reports/features/Downloads';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import { ReportContextProvider } from 'merchant_common/views/Reports/contexts/ReportsContext';
import * as modalUtils from 'merchant_common/reducers/modals';
import { getDownloadsStateWith } from './fixtures';
import * as actions from 'merchant_common/views/Reports/redux/reducer';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';

jest.spyOn(actions, 'handleLogsFilter');

jest.spyOn(modalUtils, 'openModal');

const App = (props) => {
  return (
    <ReportContextProvider dashboardType={REPORT_TEST_DASHBOARD}>
      <Downloads {...props} />
    </ReportContextProvider>
  );
};

const renderDownload = (appProps, params, initialState = {}) => {
  return render(
    <App
      location={{
        search: `/reports/downloads${params}`,
      }}
      {...appProps}
    />,
    {
      initialState,
    },
  );
};

describe('Downloads', () => {
  test('should render component without any error', () => {
    renderDownload({}, '', {});
    expect(screen.getByLabelText('Download Report Button')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Choose Logs Filter')).toBeInTheDocument();
    expect(modalUtils.openModal).toHaveBeenCalledTimes(0);
  });

  test('should change filter on dropdown select', async () => {
    renderDownload({}, '?modal=invalid', {});
    expect(modalUtils.openModal).toHaveBeenCalledTimes(0);
    await userEvent.click(screen.getByPlaceholderText('Choose Logs Filter'));
    await userEvent.click(screen.getByTestId(downloadsFilterDropdown[0].label));
    expect(actions.handleLogsFilter).toHaveBeenCalledWith({
      dashboardType: REPORT_TEST_DASHBOARD,
      filter: downloadsFilterDropdown[0].value,
    });
  });

  test('should open modal when url has modal and config params', () => {
    renderDownload({}, '?modal=download_custom_report&config=invoice');
    expect(modalUtils.openModal).toHaveBeenCalled();
  });

  test('should render app when url has modal param only, without error', () => {
    renderDownload(
      {},
      '?modal=download_report',
      getDownloadsStateWith(
        {
          filter: downloadsFilterDropdown[0].value,
        },
        {
          allConfigs: {
            data: mockConfigs,
            loading: false,
            error: false,
          },
        },
      ),
    );
    expect(modalUtils.openModal).toHaveBeenCalled();
  });

  test('should not throw error when clicked on download button', async () => {
    renderDownload(
      {},
      '',
      getDownloadsStateWith(
        {
          filter: downloadsFilterDropdown[0].value,
        },
        {
          allConfigs: {
            data: mockConfigs,
            loading: false,
            error: false,
          },
        },
      ),
    );

    await userEvent.click(screen.getByLabelText('Download Report Button'));
    screen.debug(undefined, Infinity);
    expect(modalUtils.openModal).toHaveBeenCalledTimes(1);
  });
});
