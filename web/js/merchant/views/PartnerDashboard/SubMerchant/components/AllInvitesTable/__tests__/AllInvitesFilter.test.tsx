import React from 'react';

import AllInvitesFilter from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';
import { render, screen, waitFor, userEvent } from 'test-utils';

const MOCK_LOCATION = {
  key: '',
  pathname: '/submerchants/all',
  hash: '',
  search: '',
  state: {},
};

let mockLocation = MOCK_LOCATION;
jest.mock('react-router-dom', () => {
  return {
    __esModule: true,
    ...(jest.requireActual('react-router-dom') as any),
    useLocation: () => mockLocation,
  };
});
const handlePagination = jest.fn();
const onSearch = jest.fn();

describe('All Invites Filter', () => {
  beforeEach(() => {
    mockLocation = MOCK_LOCATION;
  });
  const renderApp = () => {
    render(<AllInvitesFilter setPagination={handlePagination} count={25} onSearch={onSearch} />, {
      renderViaRouteGuard: false,
    });
  };

  test('should render all fields', () => {
    renderApp();
    expect(screen.getByLabelText('Name')).toBeInTheDocument();
    expect(screen.getByLabelText('Email ID')).toBeInTheDocument();
    expect(screen.getByLabelText('Phone Number')).toBeInTheDocument();
    expect(screen.getByLabelText('Count')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Search' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Clear' })).toBeInTheDocument();
  });

  test('should setValue to fields if search is already present in location', async () => {
    mockLocation = { ...MOCK_LOCATION, search: '?name=ABC123&count=28' };
    renderApp();
    await waitFor(() => {
      expect(screen.getByLabelText('Count')).toHaveValue('28');
    });
    expect(screen.getByLabelText('Name')).toHaveValue('ABC123');
  });

  test('should update the state and call search when fields are updated and search is clicked', async () => {
    renderApp();

    const nameField = screen.getByLabelText('Name');
    expect(nameField).toBeInTheDocument();
    await userEvent.type(nameField, 'ABCUYD');

    const emailField = screen.getByLabelText('Email ID');
    expect(emailField).toBeInTheDocument();
    await userEvent.type(emailField, 'test@email.com');

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
    mockLocation = { ...MOCK_LOCATION, search: '?name=ABC123&count=28' };
    renderApp();
    await waitFor(() => {
      expect(screen.getByLabelText('Count')).toHaveValue('28');
    });
    expect(screen.getByLabelText('Name')).toHaveValue('ABC123');

    const clearButton = screen.getByRole('button', { name: 'Clear' });
    expect(clearButton).toBeInTheDocument();
    await userEvent.click(clearButton);

    await waitFor(() => {
      expect(screen.getByLabelText('Count')).toHaveValue('25');
    });
    expect(screen.getByLabelText('Name')).toHaveValue('');
    expect(onSearch).toBeCalled();
  });

  test('should show validation errors for invalid input', async () => {
    renderApp();

    const nameField = screen.getByLabelText('Name');
    await userEvent.type(nameField, 'ABC');

    const searchButton = screen.getByRole('button', { name: 'Search' });
    expect(searchButton).toBeDisabled();

    expect(screen.getByText('Name should have at least 4 characters.')).toBeInTheDocument();

    await userEvent.type(nameField, 'ABCD');
    expect(searchButton).toBeEnabled();

    const emailField = screen.getByLabelText('Email ID');
    await userEvent.type(emailField, 'notAnEmail');
    expect(searchButton).toBeDisabled();
    expect(screen.getByText('Please enter a valid email id.')).toBeInTheDocument();
  });
});
