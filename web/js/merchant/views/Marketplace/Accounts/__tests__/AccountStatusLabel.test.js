import { screen, render } from '@testing-library/react';
import { AccountStatusListView } from 'merchant/views/Marketplace/Accounts/components/AccountStatusLabel';

const renderComponent = (props = {}) => {
  render(<AccountStatusListView {...props} />);
};

describe('Tests for AccountStatusLabel', () => {
  test('Suspended status should be visible when activation status is suspended', () => {
    renderComponent({ activationStatus: 'suspended' });
    expect(screen.getByText('Suspended')).toBeInTheDocument();
  });

  test('Tooltip should not be visible when activation status is suspended', () => {
    renderComponent({ activationStatus: 'suspended' });
    expect(screen.queryByTestId('popover-body')).not.toBeInTheDocument();
  });

  test('Tooltip should be visible when activation status is other suspended', () => {
    renderComponent({ activationStatus: 'not_activated' });
    expect(screen.getByTestId('popover-body')).toBeInTheDocument();
  });
});
