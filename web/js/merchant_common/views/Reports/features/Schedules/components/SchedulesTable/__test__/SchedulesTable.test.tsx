import React from 'react';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { render, screen } from 'test-utils';
import { SchedulesTable } from 'merchant_common/views/Reports/features/Schedules/components/SchedulesTable';
import { getSchedulesStateWith } from 'merchant_common/views/Reports/features/Schedules/__test__/fixtures';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import * as S from 'merchant_common/views/Reports/api/schedules';
import { mockSchedules } from 'merchant_common/views/Reports/redux/__test__/fixtures/schedules.fixtures';

const initiateSchedulesPoll = jest.spyOn(S, 'initiateSchedulesPoll');

describe('SchedulesTable', () => {
  const App = (props) => {
    return <SchedulesTable {...props} dashboardType={REPORT_TEST_DASHBOARD} />;
  };
  afterEach(() => {
    initiateSchedulesPoll.mockClear();
  });

  test('should render component without any error', () => {
    const initialState = getSchedulesStateWith({
      loading: true,
      allSchedules: [],
      pageTrack: 1,
      genericPoll: true,
    });
    expect(() => render(<App />, { initialState })).not.toThrow();
  });

  test('should render illustration when no schedules are passed', () => {
    const initialState = getSchedulesStateWith({
      loading: false,
      allSchedules: [],
      genericPoll: false,
    });
    render(<App />, { initialState });
    expect(screen.getByText('No Schedules Found :(')).toBeInTheDocument();
  });

  test('should handle poll success callback', () => {
    const initialState = getSchedulesStateWith({
      loading: false,
      allSchedules: [],
      genericPoll: true,
    });

    initiateSchedulesPoll.mockImplementationOnce(({ pollResSuccessCallback }: any) => {
      pollResSuccessCallback({
        total_count: mockSchedules.length,
        items: mockSchedules,
      });
      return { abort: () => {}, promise: new Promise(() => {}) };
    });
    render(<App />, { initialState });
    expect(initiateSchedulesPoll).toHaveBeenCalledTimes(1);
  });

  test('should handle poll failed callback', () => {
    const initialState = getSchedulesStateWith({
      loading: false,
      allSchedules: [],
      genericPoll: true,
    });
    initiateSchedulesPoll.mockImplementationOnce(({ pollResFailedCallback }: any) => {
      pollResFailedCallback();
      return { abort: () => {}, promise: new Promise(() => {}) };
    });
    render(<App />, { initialState });
    expect(initiateSchedulesPoll).toHaveBeenCalledTimes(1);
  });

  test('should handle poll stop callback', () => {
    const initialState = getSchedulesStateWith({
      loading: false,
      allSchedules: [],
      genericPoll: true,
    });
    initiateSchedulesPoll.mockImplementationOnce(({ onPollStopCallback }: any) => {
      onPollStopCallback();
      return { abort: () => {}, promise: new Promise(() => {}) };
    });
    render(<App />, { initialState });
    expect(initiateSchedulesPoll).toHaveBeenCalledTimes(1);
  });
});
