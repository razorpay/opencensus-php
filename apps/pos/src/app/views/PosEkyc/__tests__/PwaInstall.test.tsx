import React from 'react';
import { screen } from '@testing-library/react';
import PwaInstall from '../PwaInstall';
import { render } from 'apps/pos/src/services/test/test-utils';

describe('POS eKYC <PwaInstall />', () => {
  test('should render instructions', () => {
    render(<PwaInstall />);
    expect(screen.getByText(/Install App on your device/i)).toBeInTheDocument();
    expect(screen.getByText(/Get started seamlessly/i)).toBeInTheDocument();

    const dashboardLink = screen.getByRole('link', {
      name: /Accessing Dashboard/i,
    });

    expect(dashboardLink).toHaveAttribute('href', `/app/pos-sales`);
    expect(
      screen.getByRole('button', {
        name: /Install Now/i,
      }),
    ).toBeInTheDocument();
  });
});
