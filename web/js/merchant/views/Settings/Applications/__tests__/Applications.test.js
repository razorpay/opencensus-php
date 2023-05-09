import ApplicationContainer from 'merchant/views/Settings/Applications/index';
import { render, screen, server, waitFor } from 'test-utils';
import { oauthApplications, tokens } from './mocks/fixtures';
import { getApplications, getTokens } from './mocks/handlers';

const initialState = {
  session: {
    user: {
      isRevokeApplicationEnabled: true,
    },
  },
};

const location = {
  pathname: '/website-app-settings/applications',
};

describe('ApplicationContainer', () => {
  // application endpoint
  test('should render the applications list for oauth merchants', async () => {
    server.use(getApplications(oauthApplications));
    render(<ApplicationContainer location={location} />, { initialState });
    await waitFor(() => {
      expect(screen.queryByText('Fetching connected apps...')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Hello')).toBeInTheDocument();
  });
  test('should display a message when no connected apps are found', async () => {
    server.use(getApplications([]));
    render(<ApplicationContainer location={location} />, { initialState });
    await waitFor(() => {
      expect(screen.queryByText('Fetching connected apps...')).not.toBeInTheDocument();
    });
    expect(screen.getByText('No connected apps')).toBeInTheDocument();
  });
  // Token endpoint
  test('should render the all the tokens', async () => {
    initialState.session.user.isRevokeApplicationEnabled = false;
    server.use(getTokens(tokens));
    render(<ApplicationContainer location={location} />, { initialState });
    await waitFor(() => {
      expect(screen.queryByText('Fetching connected apps...')).not.toBeInTheDocument();
    });
    expect(screen.getAllByText('Route Test').length).toEqual(tokens.length);
  });
});
