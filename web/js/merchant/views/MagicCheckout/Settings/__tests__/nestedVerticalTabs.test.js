import { Router } from 'react-router-dom';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { createMemoryHistory } from 'history';
import NestedVerticalTabs, {
  TabItem,
} from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTab';
import { TABS } from 'merchant/views/MagicCheckout/Settings/constants';

const initState = {
  magic_settings: {
    platform: 'shopify',
    showTabHeading: false,
  },
  magicCheckout: {
    cod_order_control: true,
  },
  session: {
    user: {
      isMagicPrepayCODEnabled: true,
      isMagicCODOrderAutomationEnabled: true,
      isMagicCODEngineEnabled: true,
      role: 'owner',
    },
  },
};
const MockedComponent = () => <div>Mocked Component</div>;

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <NestedVerticalTabs {...props} />
    </Provider>
  );
};

const AppWithRouter = ({ state = {}, ...props }) => {
  return (
    <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
      <App state={state} {...props} />
    </Router>
  );
};

describe('Nested vertical tabs component', () => {
  test('Nested vertical tab should render fine', async () => {
    render(<AppWithRouter />);

    await waitFor(() => {
      TABS[initState.magic_settings.platform].forEach((item) => {
        expect(screen.getByText(item.label)).toBeInTheDocument();
      });
    });
  });

  test('should navigate to destined route', async () => {
    const { history } = render(<App />);

    const MagicIntelligenceTab = screen.getByText('Magic Intelligence');

    expect(MagicIntelligenceTab).toBeInTheDocument();

    await userEvent.click(MagicIntelligenceTab);
    expect(history.location.pathname).toEqual('/magic/settings/magic-intelligence');
  });

  test('should not show COD order automation tab if feature is not enabled', async () => {
    const customState = {
      magicCheckout: {
        cod_order_control: false,
      },
    };
    render(<AppWithRouter state={customState} />);

    await waitFor(() => expect(screen.queryByText('COD Review Workflow')).not.toBeInTheDocument());
  });

  test('tab item should be rendered properly', () => {
    const props = {
      tabHeading: 'Store settings',
      showTabHeading: true,
      tabContent: MockedComponent,
      className: 'store-settings',
    };
    render(<TabItem {...props} />);
    expect(screen.getByText('Mocked Component')).toBeInTheDocument();
  });

  test('should not display tab heading if not required', () => {
    const props = {
      tabContent: MockedComponent,
      className: 'store-settings',
    };
    render(<TabItem {...props} />);
    expect(screen.queryByText('Store settings')).not.toBeInTheDocument();
  });

  test('should not display store settings if user role is other than admin or owner', async () => {
    const customState = {
      ...initState,
      session: {
        user: {
          isMagicCODOrderAutomationEnabled: true,
          role: 'manager',
        },
      },
    };
    render(<AppWithRouter state={customState} />);
    await waitFor(() => expect(screen.queryByText('Store Settings')).not.toBeInTheDocument());
  });
});
