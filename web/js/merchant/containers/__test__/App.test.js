import { getInitialUserOrgState } from 'common/tests/utils';
import * as trackEvents from 'common/utils/analytics';
import * as utilTracker from 'common/utils/trackers';
import {
  renderApp,
  userDetails,
  orgDetails,
} from 'merchant/containers/__test__/mocks/fixtures/App';
import { screen, waitFor, userEvent, delay } from 'test-utils';
const analyticsTrackSpy = jest.spyOn(trackEvents, 'analyticsTrack');

jest.mock('common/splitz', () => ({
  withSplitzService: jest.fn(),
}));

describe('Idle timer', () => {
  beforeAll(() => {
    window.rzp_user = userDetails;
    window.rzp_org = orgDetails;
    jest.spyOn(utilTracker, `initLumberjack`).mockImplementation(() => {});
    jest.spyOn(utilTracker, `initSegment`).mockImplementation(() => {});
  });

  test.skip('should show timeout popup after 2 seconds if org feature flag "logout_admin_inactivity" is enabled', async () => {
    renderApp();
    await delay(2000);
    await waitFor(() => {
      expect(screen.getByText('Your session has Expired!')).toBeInTheDocument();
    });
  });
});

describe.skip('App container', () => {
  beforeAll(() => {
    jest.spyOn(utilTracker, `initLumberjack`).mockImplementation(() => {});
    jest.spyOn(utilTracker, `initSegment`).mockImplementation(() => {});
  });

  test('should show partner activation form when a partner is switching mode', async () => {
    const session = getInitialUserOrgState({
      isRzpOrg: true,
      userExtra: {
        ...userDetails,
        isActivated: false,
        partner_type: 'reseller',
        isPartner: (partner_type) => partner_type === 'reseller',
        splitz_experiments: {
          HhTUjZcw4WsE2V: { name: 'exposed' }, //independent_partner_kyc
          Lf6qHEprAH4UCm: { name: 'exposed' }, //universal_search_enabled
        },
      },
    });
    // Remove getter properties from session which cannot be assigned when creating new User();
    const { isOrgRZP, isOrgCurlec, isActivated, ...user } = session.user;
    window.rzp_user = user;

    renderApp({ partnerMode: 'test' }, { initialState: { session }, pathname: '/partners' });
    await waitFor(() => {
      expect(screen.getByText("YOU'RE IN TEST MODE")).toBeInTheDocument();
    });
    await userEvent.click(screen.getByTestId('test-mode-switch'));

    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner KYC Form',
        actionName: 'Opened',
        screen: 'Switch Mode',
      }),
    );
  });
});
