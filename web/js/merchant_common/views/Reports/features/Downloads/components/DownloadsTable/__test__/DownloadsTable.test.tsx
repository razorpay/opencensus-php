import React from 'react';
import { render, screen } from 'test-utils';
import { DownloadsTable } from 'merchant_common/views/Reports/features/Downloads/components/DownloadsTable';
import { getDownloadsStateWith } from 'merchant_common/views/Reports/features/Downloads/__test__/fixtures/index';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import * as S from 'merchant_common/views/Reports/api/downloads';
import { mockLogs } from 'merchant_common/views/Reports/redux/__test__/fixtures/logs.fixture';

const initialLogPollMock = jest.spyOn(S, 'initiateLogsPoll');

describe('DownloadsTable', () => {
  const App = (props) => {
    return <DownloadsTable {...props} dashboardType={REPORT_TEST_DASHBOARD} />;
  };
  afterEach(() => {
    initialLogPollMock.mockClear();
  });

  test('should render component without any error', () => {
    const initialState = getDownloadsStateWith({
      loading: true,
      logs: {},
      pageTrack: 1,
      genericPoll: true,
    });
    expect(() => render(<App />, { initialState })).not.toThrow();
  });

  test('should render illustration when no logs passed', () => {
    const initialState = getDownloadsStateWith({
      loading: false,
      logs: {},
      genericPoll: false,
    });
    render(<App />, { initialState });
    expect(screen.getByText('No Reports Found :(')).toBeInTheDocument();
  });

  test('should handle poll success callback', () => {
    const initialState = getDownloadsStateWith({
      loading: false,
      logs: {},
      genericPoll: true,
    });

    initialLogPollMock.mockImplementationOnce(({ pollResSuccessCallback }: any) => {
      pollResSuccessCallback({
        total_count: mockLogs.length,
        items: mockLogs,
      });
      return { abort: () => {}, promise: new Promise(() => {}) };
    });
    render(<App />, { initialState });
    expect(initialLogPollMock).toHaveBeenCalledTimes(1);
    console.log(initialState.reportsCore[REPORT_TEST_DASHBOARD].downloads);
  });

  test('should handle poll failed callback', () => {
    const initialState = getDownloadsStateWith({
      loading: false,
      logs: {},
      genericPoll: true,
    });
    initialLogPollMock.mockImplementationOnce(({ pollResFailedCallback }: any) => {
      pollResFailedCallback();
      return { abort: () => {}, promise: new Promise(() => {}) };
    });
    render(<App />, { initialState });
    expect(initialLogPollMock).toHaveBeenCalledTimes(1);
  });

  test('should handle poll stop callback', () => {
    const initialState = getDownloadsStateWith({
      loading: false,
      logs: {},
      genericPoll: true,
    });
    initialLogPollMock.mockImplementationOnce(({ onPollStopCallback }: any) => {
      onPollStopCallback();
      return { abort: () => {}, promise: new Promise(() => {}) };
    });
    render(<App />, { initialState });
    expect(initialLogPollMock).toHaveBeenCalledTimes(1);
  });
});
