import React from 'react';

import RetryOnError from 'merchant/views/BillMeSettings/common/components/RetryOnError';
import { screen, render, userEvent } from 'test-utils';

describe('RetryOnError', () => {
  test('should render RetryOnError with passed in prop values', async () => {
    const retryFn = jest.fn();
    render(<RetryOnError errorText="Error occurred" retryFn={retryFn} />);
    expect(screen.getByText('Error occurred')).toBeInTheDocument();
    const retryLink = screen.getByRole('button');
    await userEvent.click(retryLink);
    expect(retryFn).toHaveBeenCalledTimes(1);
  });

  test('should render RetryOnError with default props', () => {
    render(<RetryOnError errorText="" />);
    expect(screen.getByText('Access Denied')).toBeInTheDocument();
  });
});
