import { render as renderMain, screen, userEvent } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';
import CODAutomationBanner from 'merchant/views/MagicCheckout/CODOrdersTab/common/CODAutomationBanner';
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

const render = (ui, extraConfig = { state: {} }) => {
  return renderMain(ui, {
    reduxStore: storeWithInitialState({ ...initState, ...extraConfig.state }),
  });
};

describe('Automation banner component', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: jest.fn(() => ({ success: jest.fn(), initiated: jest.fn() })),
    };
  });

  test('rendering automation banner', () => {
    render(<CODAutomationBanner />);
    expect(
      screen.getByText(new RegExp(`${AUTOMATION_BANNER_SUBHEADING}`, 'i')),
    ).toBeInTheDocument();
  });

  test('automation cta should be present', () => {
    render(<CODAutomationBanner />);
    expect(
      screen.getByRole('button', {
        name: 'Automate now',
      }),
    ).toBeInTheDocument();
  });

  test('should have a navigation route', () => {
    const { container } = render(<CODAutomationBanner />);
    expect(container.querySelector('a').getAttribute('href')).toBe(AUTOMATION_TAB_LINK);
  });

  test('should set the location path when click on automate CTA', async () => {
    const { history, container } = render(<CODAutomationBanner />);

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

    render(<CODAutomationBanner />, {
      state: customState,
    });
    expect(screen.queryByText(AUTOMATION_BANNER_SUBHEADING)).toBeNull();
  });
});
