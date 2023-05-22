import store from 'merchant/store';
import EmptySettlementState from 'merchant/views/Settlements/v3/components/EmptyState';
import React from 'react';
import { render, screen } from 'test-utils';

const findTagMock = jest.fn();

store.getState = jest.fn(() => ({
  session: {
    user: {
      findTag: findTagMock,
    },
  },
}));

const renderApp = () => render(<EmptySettlementState handleAction={jest.fn()} />);

describe('EmptySettlementState', () => {
  test('should render empty settlement state content', () => {
    findTagMock.mockReturnValue(false);
    renderApp();
    expect(screen.getByText(/Get settlements in your bank account/i)).toBeInTheDocument();
    const settlementsGuideLink = screen.getByRole('button', { name: 'View settlements' });
    expect(settlementsGuideLink).toBeInTheDocument();
  });
});
