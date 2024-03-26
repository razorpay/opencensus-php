import React from 'react';

import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { render, screen, userEvent } from 'test-utils';

const errorText = 'Something went wrong';

describe('Widget->common->ErrorState', () => {
  test('should render error component with title', () => {
    render(<ErrorState text={errorText} />);
    expect(screen.getByText(errorText)).toBeVisible();
  });

  test('should render error component with title, subtitle, and retry handler', async () => {
    const mockedRetryHandler = jest.fn();
    render(<ErrorState text={errorText} retryHandler={mockedRetryHandler} />);
    expect(screen.getByText(errorText)).toBeVisible();
    await userEvent.click(screen.getByRole('button', { name: /try again/i }));
    expect(mockedRetryHandler).toHaveBeenCalledTimes(1);
  });
});
