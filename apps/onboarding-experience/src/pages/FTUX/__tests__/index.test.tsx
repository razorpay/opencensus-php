import React from 'react';
import { render, screen } from '../../../services/test/jest-utils';
import FTUXWrapper from '../index';

describe('FTUX Component', () => {
  test.skip('renders without crashing', () => {
    render(<FTUXWrapper />);
    expect(screen.getByTestId('wrapper')).toBeInTheDocument();
  });
});
