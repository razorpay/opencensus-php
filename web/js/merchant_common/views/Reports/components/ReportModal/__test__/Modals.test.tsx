import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { ReportModal } from 'merchant_common/views/Reports/components';
import * as modalFn from 'merchant_common/reducers/modals';

const closeModal = jest.spyOn(modalFn, 'closeModal');

describe('ReportModal', () => {
  const App = ({ type }) => {
    return <ReportModal dashboardType="merchant" type={type} />;
  };

  test('should render modal without error', () => {
    render(<App type="dummy" />);
    expect(screen.queryByLabelText('Close Modal')).not.toBeInTheDocument();
  });

  test('should render modal when valid type is passed', () => {
    render(<App type="download_custom_report" />);
    expect(screen.getByLabelText('Close Modal')).toBeInTheDocument();
  });

  test('should close modal without error', async () => {
    render(<App type="download_report" />);
    await userEvent.click(screen.getByLabelText('Close Modal'));
    await expect(closeModal).toHaveBeenCalled();
  });
});
