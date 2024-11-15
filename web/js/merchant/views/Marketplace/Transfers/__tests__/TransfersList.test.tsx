import React from 'react';
import { render, screen, waitForElementToBeRemoved, server } from 'common/services/test/test-utils';
import TransfersList from 'merchant/views/Marketplace/Transfers/List';
import { transfersListSuccess } from './mocks/handlers';
import { transfersData } from './mocks/fixtures';

const location = {
  search: '',
};

const state = {
  session: {
    user: {
      isDirectTransferEnabled: true,
      isOrgAllowedFunctionality: () => true,
      findTag: () => false,
      isAllowedEdit: () => true,
    },
  },
};

describe('Transfers List', () => {
  const renderApp = () => {
    render(<TransfersList location={location} />, {
      initialState: state,
    });
  };

  test('should render table with data once data is loaded', async () => {
    server.use(transfersListSuccess());
    renderApp();
    const spinner = screen.getByRole('progressbar', { name: 'Loading Table' });
    expect(spinner).toBeInTheDocument();
    await waitForElementToBeRemoved(spinner);
    expect(screen.getByText(transfersData.items[0].id)).toBeInTheDocument();
  });
});
