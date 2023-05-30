import React from 'react';
import { render, screen } from 'test-utils';
import ListFilterForm from 'merchant/views/PaymentButton/SubscriptionButton/List/ListFilter';

const defaultProps = {
  count: '25',
  form: 'paymentButtonListFilter',
  onClearAnalytics: jest.fn(),
  onSubmit: jest.fn(),
};

describe('ListFilterForm', () => {
  const renderApp = () => render(<ListFilterForm {...defaultProps} />);
  it('renders the form fields', () => {
    renderApp();
    expect(screen.getByText('Title')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Count')).toBeInTheDocument();
  });

  it('renders the component with the correct fields and labels', () => {
    renderApp();
    const searchBtn = screen.getByRole('button', { name: 'Search' });
    const clearBtn = screen.getByRole('button', { name: 'Clear' });
    expect(searchBtn).toBeInTheDocument();
    expect(clearBtn).toBeInTheDocument();
  });
});
