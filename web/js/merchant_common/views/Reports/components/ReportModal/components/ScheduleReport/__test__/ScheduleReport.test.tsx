import React from 'react';
import { render, screen, server, userEvent } from 'test-utils';
import * as modalFn from 'merchant_common/reducers/modals';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import { getOverViewStateWith } from 'merchant_common/views/Reports/features/Overview/__test__/fixtures';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';
import * as notification from 'merchant_common/reducers/notifications';
import { MANDATORY_FIELD_REQUIRED } from 'merchant_common/views/Reports/constants/notifications';
import * as scheduleAPI from 'merchant_common/views/Reports/api/schedules';
import { mockSchedules } from 'merchant_common/views/Reports/redux/__test__/fixtures/schedules.fixtures';
import { getDataDurations } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/data';
import moment from 'moment';
import { defineMatchMedia } from 'merchant_common/views/Reports/components/DateTimeRangePicker/__test__/fixtures';
import { changeScheduleStringsToNumerics } from 'merchant_common/views/Reports/features/Schedules/utils';
import { rest } from 'msw';
import ScheduleReport from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport';
import { getFormattedDate } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';

const initialState = getOverViewStateWith({
  allConfigs: {
    loading: false,
    error: false,
    data: mockConfigs,
  },
});

const TEST_CONFIG = mockConfigs[0];

const TEST_USER = {
  name: 'Rzp',
  email: 'unactivated@gmail.com',
  id: 'MID_ASDASW',
  current: 'IASD_ASD',
  isOrgAllowedFunctionality: jest.fn(() => true),
  findTag: jest.fn(),
  isMarketplaceEnabled: true,
};

defineMatchMedia(false);

const notificationSpy = jest.spyOn(notification, 'showNotification');
const scheduleSpy = jest.spyOn(scheduleAPI, 'createSchedule');

jest.mock('merchant_common/views/Reports/utils/commonUtils', () => ({
  ...(jest.requireActual('merchant_common/views/Reports/utils/commonUtils') as Record<
    string,
    unknown
  >),
  getAvailableEmails: () => ['joel.jaimon@gmail.com', 'unactivated@gmail.com', 'rzp@rzp.com'],
}));

jest.spyOn(modalFn, 'closeModal');

describe('Create Schedule Modal', () => {
  const App = ({ configId }: { configId?: string }): JSX.Element => {
    return (
      <ScheduleReport
        dashboardType={REPORT_TEST_DASHBOARD}
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
        session: {
          user: TEST_USER,
          mode: 'live',
        },
      },
    });

  it('should render modal without error', () => {
    render(<App />);
    expect(screen.getByText('Create Report Schedule')).toBeInTheDocument();
    expect(
      screen.getByText(
        'You can create schedules on your reports and automate their delivery to your email. Choose what reports, where and how frequently you want them delivered.',
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText('What report is this?')).toBeInTheDocument();
    expect(screen.queryByText('What will you receive in this report?')).toBeInTheDocument();
    expect(screen.queryByText('Who will receive this report?')).toBeInTheDocument();
    expect(screen.getByLabelText('Create Schedule')).toBeInTheDocument();
  });

  it('should open 1st section by default', () => {
    render(<App />);
    expect(screen.getByPlaceholderText('Select A Report')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Eg: Schedule At 5pm daily')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Excel or CSV')).toBeInTheDocument();
    expect(screen.queryByPlaceholderText('Select a duration to schedule.')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Custom Switch')).not.toBeInTheDocument();
  });

  it('should throw error on start download click when the mandatory fields are not filled', async () => {
    renderDetailedApp();
    await userEvent.click(screen.getByLabelText('Create Schedule'));
    expect(notificationSpy).toHaveBeenCalledWith({
      message: MANDATORY_FIELD_REQUIRED,
      type: 'error',
    });
  });

  it('should be able to fill data', async () => {
    renderDetailedApp();
    // base
    await userEvent.type(screen.getByPlaceholderText('Eg: Schedule At 5pm daily'), 'Rzp Schedule');
    await userEvent.click(screen.getByPlaceholderText('Excel or CSV'));
    await userEvent.click(screen.getByTestId('CSV'));

    // duration
    await userEvent.click(screen.getByText('What will you receive in this report?'));

    const renderText = getFormattedDate(
      {
        startDate: moment().startOf('day').add(1, 'day'),
        endDate: moment().endOf('day').add(30, 'day'),
      },
      true,
    );

    expect(screen.getByText(renderText)).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Custom Switch'));
    await userEvent.click(screen.getByLabelText('Custom Switch'));
    await userEvent.click(screen.getByPlaceholderText('Data duration covered in each report'));
    await userEvent.click(screen.getByText(getDataDurations(false)[0].label));

    await userEvent.click(screen.getByText('Who will receive this report?'));
    await userEvent.click(screen.getByLabelText('Add Recipient Field'));
    await userEvent.click(screen.getByLabelText('rzp@rzp.com'));

    await userEvent.click(screen.getByLabelText('Create Schedule'));

    await expect(scheduleSpy).toBeCalled();
  }, 10000);

  it('should be able to edit data', async () => {
    server.use(
      rest.patch('*/reporting/schedules/:scheduleId', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.delay(0),
          ctx.json({
            status_code: 200,
            success: true,
            data: {
              id: mockSchedules[0].id,
            },
          }),
        );
      }),
    );
    render(
      <ScheduleReport
        dashboardType={REPORT_TEST_DASHBOARD}
        params={{
          scheduleData: {
            ...mockSchedules[0],
            ...changeScheduleStringsToNumerics(mockSchedules[0]),
          },
        }}
      />,
      {
        initialState: {
          ...initialState,
          session: {
            user: TEST_USER,
            mode: 'live',
          },
        },
        renderViaRouteGuard: false,
      },
    );

    await userEvent.click(screen.getByText('Edit Schedule'));
    await expect(scheduleSpy).toHaveReturned();
  });
});
