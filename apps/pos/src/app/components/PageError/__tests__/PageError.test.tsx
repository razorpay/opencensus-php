import React from 'react';
import PageError from '../PageError';
import { render, screen } from 'apps/pos/src/services/test/test-utils';

describe('<PageError/>', () => {
  test('should render page error on screen with default props', () => {
    render(<PageError description="test description" isFullWidth />);
    expect(screen.getByText('Something went wrong!')).toBeInTheDocument();
    expect(screen.getByText('test description')).toBeInTheDocument();
  });

  test('should render page error on screen with props', () => {
    render(<PageError title="Random Error" description="test description" isFullWidth />);
    expect(screen.getByText('Random Error')).toBeInTheDocument();
    expect(screen.getByText('test description')).toBeInTheDocument();
  });
});
