import Sidebar from 'merchant/components/Sidebar/index';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { render, screen } from 'test-utils';

const defaultProps = {
  user: {
    current: 'abcd',
    merchants: {
      abcd: { name: 'abcd', id: '12345' },
    },
    isPartner: () => false,
  },
};

jest.mock('merchant/components/Sidebar/ActivationProgress', () => ({
  ...jest.requireActual('merchant/components/Sidebar/ActivationProgress'),
  __esModule: true,
  default: () => {
    return <div>ActivationProgress Component</div>;
  },
}));

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

const mockedFn = jest.fn();
jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component i18={{ isConfigTagEnabled: mockedFn }} {...props} />,
  useI18Service: () => ({
    isConfigTagEnabled: jest.fn(),
  }),
}));

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const renderApp = ({ props, state }) => render(<Sidebar {...props} />, { initialState: state });

describe('test for Sidebar component', () => {
  test('should hide component if onboarding.onboarding is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore({ merchant: { currency: 'MY' } }, { features: [] });

    const text = 'ActivationProgress Component';
    mockedFn.mockImplementation((path) => path === 'onboarding.onboarding');
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });

  test('should hide component if app_store.app_store is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore({ merchant: { currency: 'MY' } }, { features: [] });

    const text = 'App Store';
    mockedFn.mockImplementation((path) => path === 'app_store.app_store');
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });
});
