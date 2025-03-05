import React from 'react';
import { render, screen } from 'test-utils';

import IncorpPackageCard from '../components/IncorpPackageCard';
import { ICORP_PACKAGE_HEADER, PACKAGES } from '../constant';

describe('Test IncorpPackageCard Component', () => {
  test('Heading text render correctly', () => {
    render(<IncorpPackageCard isSmallDevice />);
    const headerText = screen.getByText(ICORP_PACKAGE_HEADER);
    expect(headerText).toBeInTheDocument();
  });
  test('renders all package items', () => {
    render(<IncorpPackageCard isSmallDevice={false} />);
    PACKAGES.forEach((packageItem) => {
      expect(screen.getByText(packageItem)).toBeInTheDocument();
    });
  });

  test('renders with correct styling for small devices', () => {
    const { getByTestId } = render(<IncorpPackageCard isSmallDevice={true} />);

    const container = getByTestId('incorp-package-container');

    expect(container).toHaveStyle('justify-content: flex-start');
  });
});
