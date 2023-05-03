import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import ListFilter from 'merchant/views/PartnerDashboard/SubMerchant/ListFilter';

const defaultProps = {
  form: 'SubmerchantListFilter',
  type: 'link',
  count: 25,
  showAppIdFilter: true,
  onSearchAnalytics: jest.fn(),
  onClearAnalytics: jest.fn(),
};

describe('ListFilter', () => {
  test('should render submerchant list filter form', () => {
    render(<ListFilter {...defaultProps} />);
    expect(screen.getByText('Account ID')).toBeInTheDocument();
    expect(screen.getAllByRole('form')).toHaveLength(1);
    expect(screen.getByRole('form')).toHaveAttribute('name', defaultProps.form);
  });
  test('should render submerchant list filter form without app id filter', () => {
    render(<ListFilter {...defaultProps} showAppIdFilter={false} />);
    expect(screen.getByText('Account ID')).toBeInTheDocument();
    expect(screen.queryByText('Application Id')).not.toBeInTheDocument();
    expect(screen.getAllByRole('form')).toHaveLength(1);
    expect(screen.getByRole('form')).toHaveAttribute('name', defaultProps.form);
  });
});
