import React from 'react';

import ErrorPage from 'merchant/views/BillMeSettings/common/components/ErrorPage';
import { screen, render } from 'test-utils';

describe('ErrorPage', () => {
  test('should render ErrorPage with default props', () => {
    render(<ErrorPage />);
    expect(screen.getByText('Something went wrong!')).toBeInTheDocument();
    expect(
      screen.getByText('We are facing some issues. Please try again later.'),
    ).toBeInTheDocument();
  });

  test('should render ErrorPage with passed in prop values', () => {
    render(<ErrorPage title="Test Title" description="Test Description" />);
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
  });
});
