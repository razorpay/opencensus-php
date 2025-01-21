import React from 'react';

import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { ERROR_PAGE_DESCRIPTION } from '@apps/digital-bills/src/utils/constants';

describe('ErrorPage', () => {
  test('should render ErrorPage with default props', () => {
    const { getByText } = renderWithWrappers(<ErrorPage description={ERROR_PAGE_DESCRIPTION} />);
    expect(getByText('Something went wrong!')).toBeInTheDocument();
    expect(getByText('We are facing some issues. Please try again later.')).toBeInTheDocument();
  });
});
