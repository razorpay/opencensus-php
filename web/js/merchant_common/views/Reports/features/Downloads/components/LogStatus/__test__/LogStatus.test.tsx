import React from 'react';
import { render, screen } from 'test-utils';
import { LogStatus } from 'merchant_common/views/Reports/features/Downloads/components/LogStatus';
import { checkDownloadsLogStatus } from 'merchant_common/views/Reports/configs/downloads.config';

describe('LogStatus', () => {
  const App = (props) => {
    return <LogStatus {...props} />;
  };

  test('should component without any error', () => {
    expect(() => render(<App />)).not.toThrow();
  });

  test('should render status when passed', () => {
    const status = 'processing';
    const returnedStatus = checkDownloadsLogStatus(status, null);
    render(<App status={status} file_id={null} />);
    expect(screen.getByText(returnedStatus)).toBeInTheDocument();
  });
});
