import React from 'react';

import Status from 'merchant/views/Transactions/v2/common/components/Status';
import { screen, render } from 'test-utils';

describe('Status', () => {
  test('should render with the correct status text', () => {
    render(<Status variant="success" content="Success" status="completed" />);
    const statusElement = screen.getByText('Completed');
    expect(statusElement).toBeInTheDocument();
  });
});
