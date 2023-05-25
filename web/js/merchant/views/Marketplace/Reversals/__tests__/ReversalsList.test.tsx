import React from 'react';
import { render, screen, server, waitForElementToBeRemoved } from 'common/services/test/test-utils';
import ReversalsList from 'merchant/views/Marketplace/Reversals/List';
import { reversalsListSuccess } from './mocks/handlers';
import { reversalsData } from './mocks/fixtures';

const location = {
  search: '',
};
describe('Reversal List', () => {
  const renderApp = () => {
    render(<ReversalsList location={location} />);
  };

  test('should render table once data is loaded', async () => {
    server.use(reversalsListSuccess());
    renderApp();
    const spinner = screen.getByTestId('spinner');
    expect(spinner).toBeInTheDocument();
    await waitForElementToBeRemoved(spinner);
    expect(screen.getByText(reversalsData.items[0].id)).toBeInTheDocument();
  });
});
