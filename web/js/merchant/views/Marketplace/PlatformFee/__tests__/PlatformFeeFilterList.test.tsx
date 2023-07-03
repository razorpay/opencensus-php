import React from 'react';
import { render, screen, waitFor, userEvent } from 'common/services/test/test-utils';
import { PlatformFeeListFilter } from 'merchant/views/Marketplace/PlatformFee/components/PlatformFeeListFilter';
import { createMemoryHistory } from 'history';

const location = {
  search: '',
  pathname: '/route/platformFee',
};
const handlePagination = jest.fn();
const onSearch = jest.fn();
let history;

describe('Platform Fee Filter', () => {
  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
  });
  const renderApp = (locationProp = location) => {
    render(
      <PlatformFeeListFilter
        location={locationProp}
        setPagination={handlePagination}
        onSearch={onSearch}
        history={history}
      />,
    );
  };

  test('should render all fields', () => {
    renderApp();
    expect(screen.getByLabelText('Platform Fee ID')).toBeInTheDocument();
    expect(screen.getByLabelText('Payment ID')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByLabelText('Recipient ID')).toBeInTheDocument();
    expect(screen.getByLabelText('Count')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Search' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Clear' })).toBeInTheDocument();
  });

  test('should setValue to fields if search is already present in location', async () => {
    renderApp({ ...location, search: '?recipient=ABC123&count=28' });
    await waitFor(() => {
      expect(screen.getByLabelText('Count')).toHaveValue('28');
    });
    expect(screen.getByLabelText('Recipient ID')).toHaveValue('ABC123');
  });

  test('should update the state and call search when fields are updated and search is clicked', async () => {
    renderApp();
    const platformFeeField = screen.getByLabelText('Platform Fee ID');
    expect(platformFeeField).toBeInTheDocument();
    await userEvent.type(platformFeeField, 'ABCUYD');

    const paymentField = screen.getByLabelText('Payment ID');
    expect(paymentField).toBeInTheDocument();
    await userEvent.type(paymentField, 'ABCUYD');

    const countField = screen.getByLabelText('Count');
    expect(countField).toBeInTheDocument();
    await userEvent.type(countField, '29');

    const searchButton = screen.getByRole('button', { name: 'Search' });
    expect(searchButton).toBeInTheDocument();
    await userEvent.click(searchButton);

    await waitFor(() => {
      expect(onSearch).toBeCalled();
    });
  });

  test('should reset the value to initial state and call the API without any filter when reset is clicked', async () => {
    renderApp({ ...location, search: '?recipient=ABC123&count=28' });
    await waitFor(() => {
      expect(screen.getByLabelText('Count')).toHaveValue('28');
    });
    expect(screen.getByLabelText('Recipient ID')).toHaveValue('ABC123');

    const clearButton = screen.getByRole('button', { name: 'Clear' });
    expect(clearButton).toBeInTheDocument();
    await userEvent.click(clearButton);

    await waitFor(() => {
      expect(screen.getByLabelText('Count')).toHaveValue('25');
    });
    expect(screen.getByLabelText('Recipient ID')).toHaveValue('');
    expect(onSearch).toBeCalled();
  });
});
