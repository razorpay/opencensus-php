import EmptySettlementState from 'merchant/views/Settlements/v3/components/EmptyState';
import { render, screen } from 'test-utils';
import React from 'react';
import store from 'merchant/store';

const findTagMock = jest.fn();

store.getState = jest.fn(() => ({
  session: {
    user: {
      findTag: findTagMock,
    },
  },
}));

const renderApp = () => render(<EmptySettlementState />);

describe('EmptySettlementState', () => {
  test('should render empty settlement state content', () => {
    findTagMock.mockReturnValue(false);
    renderApp();
    expect(screen.getByText(/Receive settlements in your bank account/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Settlement is the process by which your collected payments get deposited in your bank account. They will be shown here/i,
      ),
    ).toBeInTheDocument();
    const settlementsGuideLink = screen.getByRole('link', { name: 'Settlements guide' });
    expect(settlementsGuideLink).toBeInTheDocument();
    expect(settlementsGuideLink).toHaveAttribute(
      'href',
      'https://razorpay.com/docs/payments/settlements/',
    );
  });

  test('should not render settlements guide link when user.findTag returns true', () => {
    findTagMock.mockReturnValue(true);
    renderApp();
    const settlementsGuideLink = screen.queryByRole('link', { name: 'Settlements guide' });
    expect(settlementsGuideLink).not.toBeInTheDocument();
  });
});
