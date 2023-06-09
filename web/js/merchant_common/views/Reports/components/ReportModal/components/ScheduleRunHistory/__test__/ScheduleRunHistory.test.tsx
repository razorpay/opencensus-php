import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';
import * as runHistoryAPI from 'merchant_common/views/Reports/api/runHistory';
import { mockSchedules } from 'merchant_common/views/Reports/redux/__test__/fixtures/schedules.fixtures';
import { changeScheduleStringsToNumerics } from 'merchant_common/views/Reports/features/Schedules/utils';
import ScheduleRunHistory from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/ScheduleRunHistory';

const getHistory = jest.spyOn(runHistoryAPI, 'initiateScheduleRunHistoryPoll');

describe('Schedule Run History Modal', () => {
  const renderApp = () => {
    return render(
      <ScheduleRunHistory
        dashboardType={REPORT_TEST_DASHBOARD}
        params={{
          scheduleData: {
            ...mockSchedules[0],
            ...changeScheduleStringsToNumerics(mockSchedules[0]),
          },
        }}
      />,
    );
  };
  it('should render component without any error', async () => {
    renderApp();
    expect(screen.getByText('History of Report Run')).toBeInTheDocument();
    await userEvent.click(screen.getByPlaceholderText('Choose Logs Filter'));
    await userEvent.click(screen.getByTestId(downloadsFilterDropdown[0].label));
    await userEvent.click(screen.getByLabelText('Edit Schedule Button'));
    expect(getHistory).toHaveBeenCalled();
  });
});
