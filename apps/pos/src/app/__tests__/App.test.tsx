import React from 'react';
import { screen, render } from 'apps/pos/src/services/test/test-utils';
import App from 'apps/pos/src/app/App';

describe('App', () => {
  test('should render POS App on screen', () => {
    render(<App />);
    expect(screen.getByText('Dashboard')).toBeInTheDocument();
  });
});
