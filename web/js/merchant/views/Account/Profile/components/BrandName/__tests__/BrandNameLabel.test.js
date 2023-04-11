import React from 'react';
import { render, screen } from 'test-utils';
import BrandNameLabel from 'merchant/views/Account/Profile/components/BrandName/BrandNameLabel';

const renderApp = () => render(<BrandNameLabel />);

describe('Brand Name Label', () => {
  test('should render Brand Name Label component', () => {
    renderApp();
    expect(screen.getByText('Brand Name')).toBeInTheDocument();
  });
});
