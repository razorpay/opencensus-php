import React from 'react';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { render, screen, userEvent } from 'test-utils';
import { Schedules } from 'merchant_common/views/Reports/features/Schedules';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import { ReportContextProvider } from 'merchant_common/views/Reports/contexts/ReportsContext';
import * as modalUtils from 'merchant_common/reducers/modals';
import * as actions from 'merchant_common/views/Reports/redux/reducer';
import { schedulesFilterDropdown } from 'merchant_common/views/Reports/features/Schedules/data/dropdownOptions';
import * as trackEvents from 'merchant_common/views/Reports/configs/analytics.config';
import { getDownloadsStateWith } from 'merchant_common/views/Reports/features/Downloads/__test__/fixtures';

jest.spyOn(actions, 'handleScheduleFilter');
const trackScheduleSection = jest.spyOn(trackEvents, 'trackScheduleSection');
jest.spyOn(modalUtils, 'openModal');

const App = (props) => {
  return (
    <ReportContextProvider dashboardType={REPORT_TEST_DASHBOARD}>
      <Schedules {...props} />
    </ReportContextProvider>
  );
};

const renderSchedule = (appProps?, initialState = {}) => {
  return render(<App {...appProps} />, {
    initialState,
  });
};

describe('Downloads', () => {
  test('should render component without any error', () => {
    renderSchedule();
    expect(screen.getByPlaceholderText('Choose Schedules Filter')).toBeInTheDocument();
    expect(modalUtils.openModal).toHaveBeenCalledTimes(0);
  });

  test('should trigger load event on mount', () => {
    renderSchedule(
      {},
      getDownloadsStateWith(
        {},
        {
          allConfigs: {
            loading: false,
            error: false,
          },
        },
      ),
    );
    expect(trackScheduleSection).toHaveBeenCalledWith({
      actionName: 'Loaded',
      dashboardType: 'merchant',
    });
  });

  test('should change filter on dropdown select', async () => {
    renderSchedule();
    expect(modalUtils.openModal).toHaveBeenCalledTimes(0);
    await userEvent.click(screen.getByPlaceholderText('Choose Schedules Filter'));
    await userEvent.click(screen.getByTestId(schedulesFilterDropdown[0].label));
    expect(actions.handleScheduleFilter).toHaveBeenCalledWith({
      dashboardType: REPORT_TEST_DASHBOARD,
      filter: schedulesFilterDropdown[0].value,
    });
  });
});
