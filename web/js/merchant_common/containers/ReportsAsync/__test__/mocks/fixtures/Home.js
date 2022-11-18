import store from 'merchant/store';
import User from 'merchant/models/User';
import Home from 'merchant/views/ReportsAsync/Home';
import { render } from 'test-utils';

// Adding mocks and renderApp in fixtures is affecting other tests
jest.mock('merchant/components/Announcements/ZapierBanner/ZapierBanner', () => {
  return {
    __esModule: true,
    default: () => <div>Zapier Banner</div>,
  };
});

jest.mock('merchant/components/TestModeBanner', () => {
  return {
    __esModule: true,
    default: () => <div>Test Mode Banner</div>,
  };
});

jest.mock('merchant/components/EasterEgg', () => {
  return {
    __esModule: true,
    default: () => <div>Easter Egg</div>,
  };
});

jest.mock('merchant_common/containers/ReportsAsync/GenerateReportPanel', () => {
  return {
    __esModule: true,
    default: ({ onGenerateReport, accounts, customConfigs }) => (
      <div>
        <button
          // config_id of first config
          onClick={() =>
            onGenerateReport({ emails: [], config_id: 'config_xLTz2xSyPrbhyJ' }, 'acc_123')
          }
        >
          Generate Report
        </button>
        <p>{accounts?.count} Accounts found</p>
        <p>Default account name - {accounts?.accounts?.[0]?.name}</p>
        <p>{customConfigs.length} Custom Configs Found</p>
      </div>
    ),
  };
});

jest.mock('merchant_common/containers/ReportsAsync/Logs/List', () => {
  return {
    __esModule: true,
    default: ({ onLoadMoreClick, items }) => {
      return (
        <div>
          Log list component
          <button onClick={onLoadMoreClick}>Load More Logs</button>
          <p>{items?.length} Logs found</p>
        </div>
      );
    },
  };
});

const storeData = store.getState();

const getDefaultUserObj = (props) =>
  new User({
    name: 'test-user',
    current: 'test-id',
    ...props,
  });

const defaultState = {
  session: {
    ...storeData.session,
    user: getDefaultUserObj(),
  },
};

const renderApp = ({ initialState } = {}) => {
  return render(<Home />, { initialState: { ...defaultState, ...initialState } });
};

export { defaultState, getDefaultUserObj, renderApp };
