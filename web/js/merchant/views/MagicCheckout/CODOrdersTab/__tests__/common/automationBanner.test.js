import { Router } from 'react-router-dom';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CODAutomationBanner from 'merchant/views/MagicCheckout/CODOrdersTab/common/CODAutomationBanner';
import { createMemoryHistory } from 'history';
import {
  AUTOMATION_BANNER_SUBHEADING,
  AUTOMATION_TAB_LINK,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const initState = {
  session: {
    user: {
      isMagicCODOrderAutomationEnabled: true,
    },
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <CODAutomationBanner {...props} />
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

describe('Automation banner component', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: jest.fn(() => ({ success: jest.fn(), initiated: jest.fn() })),
    };
  });

  test('rendering automation banner', () => {
    render(<AppWithRouter />);
    expect(
      screen.getByText(new RegExp(`${AUTOMATION_BANNER_SUBHEADING}`, 'i')),
    ).toBeInTheDocument();
  });

  test('automation cta should be present', () => {
    render(<AppWithRouter />);
    expect(
      screen.getByRole('button', {
        name: 'Automate now',
      }),
    ).toBeInTheDocument();
  });

  test('should have a navigation route', () => {
    const { container } = render(<AppWithRouter />);
    expect(container.querySelector('a').getAttribute('href')).toBe(AUTOMATION_TAB_LINK);
  });

  test('should set the location path when click on automate CTA', async () => {
    const { history, container } = render(<App />);

    expect(container.querySelector('a').getAttribute('href')).toBe(AUTOMATION_TAB_LINK);

    const automateCTA = screen.getByRole('button', {
      name: 'Automate now',
    });

    expect(automateCTA).toBeInTheDocument();

    await userEvent.click(automateCTA);
    expect(history.location.pathname).toEqual(AUTOMATION_TAB_LINK);
  });

  test('should return null when automation feature is not enabled', () => {
    const customState = {
      session: {
        user: {
          isMagicCODOrderAutomationEnabled: false,
        },
      },
    };

    render(<AppWithRouter state={customState} />);
    expect(screen.queryByText(AUTOMATION_BANNER_SUBHEADING)).toBeNull();
  });
});
