import React from 'react';
import { render, screen, userEvent, delay } from 'test-utils';
import { ReportModal } from 'merchant_common/views/Reports/components';
import * as modalFn from 'merchant_common/reducers/modals';
import moment from 'moment';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';

const closeModal = jest.spyOn(modalFn, 'closeModal');

const TEST_USER = {
  name: 'Rzp',
  email: 'rzp@gmail.com',
  id: 'MID_ASDASW',
  current: 'IASD_ASD',
  isOrgAllowedFunctionality: jest.fn(() => true),
  findTag: jest.fn(),
  isMarketplaceEnabled: true,
};

describe('Download Custom Reports', () => {
  const App = () => {
    return (
      <ReportModal
        dashboardType={REPORT_TEST_DASHBOARD}
        type="download_custom_report"
        params={{
          selectedConfig: 'invoice',
        }}
      />
    );
  };

  test('should render modal without error', () => {
    render(<App />);
    expect(screen.getByText('Download report for your business')).toBeInTheDocument();
    expect(
      screen.getByText(
        `A new improved version of reports now available for you to download. You can now select the specific date and time period for which you would like to see the report.`,
      ),
    ).toBeInTheDocument();
    expect(screen.getByLabelText('Close Modal')).toBeInTheDocument();
  });

  test('should not open link if fields are not validated', async () => {
    render(<App />);
    await userEvent.click(screen.getByLabelText('Start Download'));
    expect(screen.queryByText('Please fill all the mandatory data.')).toBeInTheDocument();
  });

  test('should open link if fields are validated', async () => {
    jest.spyOn(window, 'setTimeout');
    render(<App />, {
      initialState: {
        session: {
          user: TEST_USER,
          mode: 'live',
          org: {
            id: 'ASDFGHJ',
          },
        },
      },
    });
    await userEvent.click(screen.getByLabelText('Selected Month Field'));
    await userEvent.click(screen.getByLabelText(`Months Range -> ${moment().format('MMM')}`));
    await userEvent.click(screen.getByLabelText('Selected Year Field'));
    await userEvent.click(screen.getByLabelText(`Select ${moment().format('YYYY')}`));
    await userEvent.click(screen.getByLabelText('Start Download'));
    await delay(1000);
    expect(screen.queryByText('Please fill all the mandatory data.')).not.toBeInTheDocument();
    expect(closeModal).toHaveBeenCalled();
  });
});
