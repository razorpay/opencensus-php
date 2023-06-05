import React from 'react';
import { render, screen } from 'test-utils';
import { ScheduleStatus } from 'merchant_common/views/Reports/features/Schedules/components/ScheduleStatus';
import { checkScheduleStatus } from 'merchant_common/views/Reports/configs/schedule.config';

describe('ScheduleStatus', () => {
  const App = (props) => {
    return <ScheduleStatus {...props} />;
  };

  test('should component without any error', () => {
    expect(() => render(<App />)).not.toThrow();
  });

  test('should render status when passed', () => {
    const status = 'active';
    const returnedStatus = checkScheduleStatus(status);
    render(<App status={status} file_id={null} />);
    expect(screen.getByText(returnedStatus)).toBeInTheDocument();
  });
});
