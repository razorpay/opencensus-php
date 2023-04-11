import React from 'react';
import { render, screen } from 'test-utils';
import BrandName from 'merchant/views/Account/Profile/components/BrandName/BrandName';

jest.mock('merchant/views/Account/Profile/components/BrandName/BrandNameLabel', () => ({
  __esModule: true,
  default: () => <>Brand Name Label</>,
}));

jest.mock('merchant/views/Account/Profile/components/BrandName/BrandNameValue', () => ({
  __esModule: true,
  default: () => <>Brand Name Value</>,
}));

const renderApp = ({ user } = {}) => {
  const renderOutput = render(<BrandName />, {
    initialState: {
      session: {
        user: {
          activation_status: 'activated',
          business_type: 1,
          isAdminOrOwner: true,
          isAccountAndSettingsRevampEnabled: true,
          ...user,
        },
      },
    },
  });
  return renderOutput;
};

describe('Brand Name', () => {
  test('should not render Brand Name component when user is not admin or owner', () => {
    renderApp({
      user: {
        isAdminOrOwner: false,
      },
    });
    expect(screen.queryByText('Brand Name Label')).not.toBeInTheDocument();
    expect(screen.queryByText('Brand Name Value')).not.toBeInTheDocument();
  });

  test('should render Brand Name component when user isAccountAndSettingsRevampEnabled is true', () => {
    renderApp();
    expect(screen.getByText('Brand Name Label')).toBeInTheDocument();
    expect(screen.getByText('Brand Name Value')).toBeInTheDocument();
  });

  test('should render Brand Name component when user isAccountAndSettingsRevampEnabled is false', () => {
    renderApp({
      user: {
        isAccountAndSettingsRevampEnabled: false,
      },
    });
    expect(screen.getByText(/Brand Name Label/)).toBeInTheDocument();
    expect(screen.getByText(/Brand Name Value/)).toBeInTheDocument();
  });
});
