import React from 'react';
import Title from 'self-serve/src/App/Transactions/v2/common/components/Title';
import { screen, render } from '../../../../../../services/test/test-utils';

describe('Title', () => {
  test('should render children correctly with the specified styles', () => {
    render(<Title>Test Title</Title>);
    const titleElement = screen.getByText('Test Title');
    expect(titleElement).toHaveStyle(`
      font-weight: 600;
    `);
  });

  test('should render custom children', () => {
    render(<Title>Custom Title</Title>);
    const customTitleElement = screen.getByText('Custom Title');
    expect(customTitleElement).toBeInTheDocument();
  });
});
