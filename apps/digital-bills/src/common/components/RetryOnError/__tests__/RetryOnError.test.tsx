import React from 'react';

import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';

describe('RetryOnError', () => {
  test('should render RetryOnError with passed in prop values', async () => {
    const retryFn = jest.fn();
    const { getByText, getByRole } = renderWithWrappers(
      <RetryOnError errorText="Error occurred" retryFn={retryFn} />,
    );
    expect(getByText('Error occurred')).toBeInTheDocument();
    const retryLink = getByRole('button');
    await userEvent.click(retryLink);
    expect(retryFn).toHaveBeenCalledTimes(1);
  });

  test('should render RetryOnError with default props', () => {
    const { getByText } = renderWithWrappers(<RetryOnError errorText="" />);
    expect(getByText('Error')).toBeInTheDocument();
  });
});
