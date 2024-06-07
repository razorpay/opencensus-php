import React from 'react';
import { screen } from '@testing-library/react';
import { Provider } from 'react-redux';

import store from 'merchant/store';
import { render } from 'test-utils';

import OffersList from '../List';

jest.mock('common/ui/Spinner', () => () => <div>Loading...</div>);
jest.mock('merchant/components/EmptyList', () => ({
  EmptyListWithTableRow: () => <div>There are no offers yet!! Start creating new offers now.</div>,
}));

describe('OffersList Component', () => {
  const renderWithProvider = () => {
    return render(
      <Provider store={store}>
        <OffersList />
      </Provider>,
    );
  };

  it('should render the loading state', () => {
    store.getState = jest.fn(() => ({
      offers: {
        user: { id: 1 },
        loading: true,
        items: [],
      },
    }));

    renderWithProvider();
    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  it('should render empty state when there are no offers', () => {
    store.getState = jest.fn(() => ({
      offers: {
        user: { id: 1 },
        loading: false,
        items: [],
      },
    }));

    renderWithProvider();
    expect(
      screen.getByText('There are no offers yet!! Start creating new offers now.'),
    ).toBeInTheDocument();
  });
});
