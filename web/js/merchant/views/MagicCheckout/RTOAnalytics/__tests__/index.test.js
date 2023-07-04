import { render, screen, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import RTOAnalytics from 'merchant/views/MagicCheckout/RTOAnalytics';
import {
  RTO_ANALYTICS_INIT_STATE,
  TABS,
} from 'merchant/views/MagicCheckout/RTOAnalytics/__tests__/mocks/fixtures';

jest.mock('merchant/views/MagicCheckout/RTOAnalytics/containers/Header', () => () => (
  <div>Header section</div>
));

jest.mock('merchant/views/MagicCheckout/RTOAnalytics/common/RiskReportBanner', () => () => (
  <div>Risk report banner</div>
));

const OverviewMockedComponent = () => <div>Overview tab</div>;
const RiskReportMockedComponent = () => <div>Risk report tab</div>;
const RTOInsightsMockedComponent = () => <div>RTO insights tab</div>;

jest.mock('merchant/views/MagicCheckout/RTOAnalytics/constants', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/RTOAnalytics/constants'),
  TABS: {
    OVERVIEW: {
      label: 'Overview',
      Component: OverviewMockedComponent,
      eventName: 'Overview',
    },
    RISK_REPORT: {
      label: 'Risk Report',
      Component: RiskReportMockedComponent,
      eventName: 'RiskReport',
    },
    ORDER_INSIGHTS: {
      label: 'RTO Insights',
      Component: RTOInsightsMockedComponent,
      eventName: 'RTOInsights',
    },
  },
}));

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...RTO_ANALYTICS_INIT_STATE, ...state })}>
      <RTOAnalytics {...props} />
    </Provider>,
  );
};

describe('RTO analytics component', () => {
  test('should show spinner when RTO analytics is loading', async () => {
    const customState = {
      magicRTOAnalytics: {
        ...RTO_ANALYTICS_INIT_STATE.magicRTOAnalytics,
        loading: true,
      },
    };

    renderApp({ state: customState });
    await waitFor(() => {
      expect(screen.getByTestId('spinner')).toBeInTheDocument();
    });
  });

  test.each(TABS)('each tab should render properly', async (tab) => {
    renderApp();

    const analyticTab = screen.getByText(tab.labelName, { exact: true });

    userEvent.click(analyticTab);

    await waitFor(() => {
      expect(screen.getByText(new RegExp(tab.displayText, 'i'))).toBeInTheDocument();
    });
  });

  test('should show risk report banner', async () => {
    renderApp();

    const riskReportTab = screen.getByText(/^Risk Report?/i);
    userEvent.click(riskReportTab);

    await waitFor(() => {
      expect(screen.queryByText(/^Risk report banner?/i)).toBeInTheDocument();
    });
  });
});
