import ApplicationContainer from 'merchant/views/Settings/Applications/index';
import { render, screen, server, waitFor, userEvent } from 'test-utils';
import { oauthApplications, partnerApplications, tokens } from './mocks/fixtures';
import { getApplications, getPartnerApplications, getTokens } from './mocks/handlers';
import * as analytics from 'common/utils/analytics';

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

  test('should send analytics event on clicking delete and confirming delete', async () => {
    const partnerApplicationLocation = {
      pathname: '/partners/applications',
    };
    server.use(getPartnerApplications(partnerApplications));
    render(<ApplicationContainer location={partnerApplicationLocation} />, { initialState });
    await waitFor(() => {
      expect(screen.queryByTestId('skeleton-loader')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Acme App')).toBeInTheDocument();
    const deleteApp = screen.getByRole('button', { name: 'Delete Application' });
    await userEvent.click(deleteApp);
    const confirmDelete = screen.getByRole('button', { name: 'Delete' });
    await userEvent.click(confirmDelete);
    expect(analytics.analyticsTrackWithUserInfo).toBeCalledTimes(2);
    expect(analytics.analyticsTrackWithUserInfo).toBeCalledWith({
      screen: 'Partnership',
      actionName: 'Clicked',
      objectName: 'Revoke access Cta',
      properties: {
        applicationName: partnerApplications[0].name,
        appID: partnerApplications[0].id,
        createdOn: partnerApplications[0].created_at,
      },
    });
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
