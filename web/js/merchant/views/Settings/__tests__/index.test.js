import * as analytics from 'common/utils/analytics';
import { renderApp, defaultProps } from 'merchant/views/Settings/__tests__/mocks/fixtures/index';
import { screen, waitFor } from 'test-utils';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: {} }),
}));

beforeAll(() => {
  jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
});

describe('Settings page', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {
      onbr: () => {
        return {
          success: jest.fn(),
        };
      },
    };
  });

  test('should render "Rectangular Logo" option', async () => {
    const initialState = {
      session: {
        user: {
          isCustomMerchantUPIQR: true,
          isFeatureEnabled: jest.fn(() => true),
          isAllowedView: jest.fn(() => false),
          isOrgAllowedFunctionality: jest.fn(() => false),
        },
      },
    };
    const props = {
      ...defaultProps,
      showBranding: true,
    };
    renderApp(initialState, props);
    await waitFor(() => {
      expect(screen.queryByTestId('loader-dots')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Rectangular Logo')).toBeInTheDocument();
  });

  test('should not render "Rectangular Logo" option', async () => {
    const initialState = {
      session: {
        user: {
          isCustomMerchantUPIQR: false,
          isFeatureEnabled: jest.fn(() => true),
          isAllowedView: jest.fn(() => false),
          isOrgAllowedFunctionality: jest.fn(() => false),
        },
      },
    };
    const props = {
      ...defaultProps,
      showBranding: true,
    };
    renderApp(initialState, props);
    await waitFor(() => {
      expect(screen.queryByTestId('loader-dots')).not.toBeInTheDocument();
    });
    expect(screen.queryByText('Rectangular Logo')).not.toBeInTheDocument();
  });
});
