import { screen, waitFor, delay } from 'test-utils';
import {
  renderApp,
  userDetails,
  orgDetails,
} from 'merchant/containers/__test__/mocks/fixtures/App';
import * as utilTracker from 'common/utils/trackers';

describe('Idle timer', () => {
  beforeAll(() => {
    window.rzp_user = userDetails;
    window.rzp_org = orgDetails;
    jest.spyOn(utilTracker, `initLumberjack`).mockImplementation(() => {});
    jest.spyOn(utilTracker, `initSegment`).mockImplementation(() => {});
  });

  test('should show timeout popup after 2 seconds if org feature flag "logout_admin_inactivity" is enabled', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('link', { name: 'Wallet' })).toBeInTheDocument();
    });
    await delay(2000);
    await waitFor(() => {
      expect(screen.getByText('Your session has Expired!')).toBeInTheDocument();
    });
  });
});
