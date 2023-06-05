import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { ReportModal } from 'merchant_common/views/Reports/components';
import * as modalFn from 'merchant_common/reducers/modals';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';

const closeModal = jest.spyOn(modalFn, 'closeModal');

describe('Download Custom Reports', () => {
  const App = () => {
    return (
      <ReportModal
        dashboardType={REPORT_TEST_DASHBOARD}
        type="confirm_modal"
        params={{
          modalConfig: {
            title: 'Please Confirm!',
            desc: `Are you sure you want to confirm?`,
            confirmBtn: {
              onClick: () => {},
              icon: () => <></>,
              label: `Test Button`,
            },
            alert: {
              intent: 'negative',
              description: `Negative Alert.`,
            },
          },
        }}
      />
    );
  };

  it('should render app without error', () => {
    expect(() => render(<App />)).not.toThrow();
  });

  it('should show all the required info', () => {
    render(<App />);

    expect(screen.getByText('Please Confirm!')).toBeInTheDocument();
    expect(screen.getByText('Are you sure you want to confirm?')).toBeInTheDocument();
    expect(screen.getByText('Negative Alert.')).toBeInTheDocument();
    expect(screen.getByLabelText('Cancel')).toBeInTheDocument();
    expect(screen.getByLabelText('Test Button')).toBeInTheDocument();
  });

  it('should close modal on cancel click', async () => {
    render(<App />);
    expect(screen.getByLabelText('Cancel')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Cancel'));
    expect(closeModal).toHaveBeenCalled();
  });
});
