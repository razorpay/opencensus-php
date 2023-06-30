import React from 'react';
import { render, screen, waitFor, userEvent } from 'common/services/test/test-utils';
import { AllInvitesFilter } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';
import { createMemoryHistory } from 'history';

const location = {
  search: '',
  pathname: '/submerchants/all',
};
const handlePagination = jest.fn();
const onSearch = jest.fn();
let history;

describe('All Invites Filter', () => {
  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
  });
  const renderApp = (locationProp = location) => {
    render(
      <AllInvitesFilter
        location={locationProp}
        setPagination={handlePagination}
        count={25}
        onSearch={onSearch}
        history={history}
      />,
    );
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
    renderApp({ ...location, search: '?name=ABC123&count=28' });
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
    renderApp({ ...location, search: '?name=ABC123&count=28' });
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
