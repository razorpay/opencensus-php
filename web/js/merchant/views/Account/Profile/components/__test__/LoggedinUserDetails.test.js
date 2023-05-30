import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import LoggedInUserDetails from 'merchant/views/Account/Profile/components/LoggedInUserDetails';

jest.mock('merchant/components/DetailRow', () => ({
  __esModule: true,
  default: ({ label, value }) => (
    <div>
      <div>{label}</div>
      <div>{value()}</div>
    </div>
  ),
}));

describe('LoggedInUserDetails', () => {
  const props = {
    isOrgRZP: true,
    loggedInUser: {
      name: 'John Doe',
      email: 'john.doe@example.com',
    },
    loggedInUserRole: 'owner',
    handleUpdateClick: jest.fn(),
    isEmailSelfServeEnabled: true,
    openModal: jest.fn(),
  };

  const renderApp = () => {
    render(<LoggedInUserDetails {...props} />);
  };

  it('renders without crashing', () => {
    renderApp();
  });

  it('displays the user name', () => {
    renderApp();
    expect(screen.getByText('User Name')).toBeInTheDocument();
    expect(screen.getByText('John Doe')).toBeInTheDocument();
  });

  it('displays the login email and edit button for owner of org RZP with self-serve enabled', async () => {
    renderApp();
    expect(screen.getByText('Login Email')).toBeInTheDocument();
    expect(screen.getByText('john.doe@example.com')).toBeInTheDocument();
    await userEvent.click(screen.getByTestId('loggedin-user-email-edit'));
    expect(props.handleUpdateClick).toHaveBeenCalled();
  });

  it('displays the role label', () => {
    renderApp();
    expect(screen.getByText('Role')).toBeInTheDocument();
    expect(screen.getByText('Owner')).toBeInTheDocument();
  });
});
