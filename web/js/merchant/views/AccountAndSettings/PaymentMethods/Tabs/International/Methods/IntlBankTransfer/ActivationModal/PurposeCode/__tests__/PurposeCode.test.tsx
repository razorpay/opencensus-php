import React from 'react';

import { render, screen, waitFor, userEvent } from 'test-utils';

import {
  server,
  queryClient,
  purposeCodeHandlers,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/PurposeCode/__tests__/mocks/handlers';

import PurposeCode from '../index';

describe('PurposeCode', () => {
  beforeAll(() => server.listen());
  beforeEach(() => {
    server.use(purposeCodeHandlers.success());
  });
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });
  afterAll(() => server.close());

  it('should render without breaking', () => {
    render(<PurposeCode />);

    expect(screen.getByText('Purpose Code')).toBeInTheDocument();
    expect(screen.getByTestId('search-purpose-code')).toBeInTheDocument();
    expect(screen.getByTestId('select-purpose-group')).toBeInTheDocument();
  });

  it('should fetch and show purpose codes radio buttons', async () => {
    render(<PurposeCode />);

    await waitFor(() => {
      expect(screen.getByText(/P0101/)).toBeInTheDocument();
    });

    expect(screen.getByText(/P0102/)).toBeInTheDocument();
    expect(screen.getByText(/P0201/)).toBeInTheDocument();
    expect(screen.getByText(/P0202/)).toBeInTheDocument();
  });
});
