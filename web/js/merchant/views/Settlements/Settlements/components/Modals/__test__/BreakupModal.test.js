import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, server } from 'test-utils';
import BreakupModal from 'merchant/views/Settlements/Settlements/components/Modals/BreakupModal';
import {
  initialState,
  props,
} from 'merchant/views/Settlements/Settlements/components/Modals/__test__/mocks/fixtures/BreakupModal';
import * as handlers from 'merchant/views/Settlements/Settlements/components/Modals/__test__/mocks/handlers';

describe('BreakupModal.js', () => {
  const renderApp = (state = initialState, props = {}) =>
    render(<BreakupModal {...props} />, {
      state,
      showModal: true,
    });

  test('should render table tag', async () => {
    renderApp(initialState, props);

    await waitFor(() => {
      expect(screen.queryByRole('table')).toBeInTheDocument();
    });
  });

  test('should render breakup info meta', async () => {
    server.use(handlers.breakupModalSuccessHandler());
    renderApp(initialState, props);

    await waitFor(() => {
      expect(screen.queryByRole('table')).toBeInTheDocument();
    });

    expect(screen.getByText('Breakup for #setl_JFeIgD63bF8doh')).toBeInTheDocument();
    expect(screen.getByText(/Total settled amount/i)).toBeInTheDocument();
    expect(screen.getByText(/Close/i)).toBeInTheDocument();
  });

  test('should render alert on fetch error', async () => {
    server.use(handlers.breakupModalErrorHandler());
    renderApp(initialState, props);

    await waitFor(() => {
      expect(screen.getByText('No data found!')).toBeInTheDocument();
    });

    await waitFor(() => {
      expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    });
  });

  test('should render RM as currency symbol when currency passed is MYR', async () => {
    server.use(handlers.breakupModalSuccessHandler());

    render(<BreakupModal {...props} />, {
      initialState: {
        ...initialState,
        home: {
          settlement_amount: {
            data: {
              settlement_currency: 'MYR',
            },
          },
        },
      },
    });
    await waitFor(() => {
      const currencySymbols = screen.getAllByText('RM');
      const currencySymbol = currencySymbols[1];
      expect(currencySymbol).toHaveTextContent('RM');
    });
  });
});
