import React from 'react';

import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import renderWithWrappers from '@apps/digital-bills/src/utils/testing/renderWithWrappers';

describe('ErrorPage', () => {
  test('should render ErrorPage as expected', () => {
    const { getByText } = renderWithWrappers(<ErrorPage description="Please try later" />);
    expect(getByText('Something went wrong!')).toBeInTheDocument();
    expect(getByText('Please try later')).toBeInTheDocument();
  });
});
