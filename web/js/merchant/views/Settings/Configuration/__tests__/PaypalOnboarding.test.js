import store from 'merchant/store';
import PaypalOnboardingButton from 'merchant/views/Settings/Configuration/PaypalOnboarding';
import { render, waitFor, screen } from 'test-utils';

const globalState = store.getState();

const renderApp = (initialState = {}, props = {}) => {
  render(<PaypalOnboardingButton {...props} />, {
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: initialState?.session?.user ?? globalState?.session?.user,
      },
    },
  });
};

describe('test for PaypalOnboardingButton component', () => {
  it('v1 - should show Link Account CTA if org feature flag "vas_link_wallets" is enabled ', async () => {
    const initialState = {
      session: {
        user: { isLinkAccountEnabled: true },
      },
    };
    renderApp(initialState);

    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: 'Link Account',
        }),
      ).toBeInTheDocument();
    });
  });

  it('v1 - should show Link Account CTA if org feature "hide_instrument_request" & flag "vas_link_wallets" are enabled ', async () => {
    const initialState = {
      session: {
        user: { isInstrumentRequestHidden: true, isLinkAccountEnabled: true },
      },
    };
    renderApp(initialState);

    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: 'Link Account',
        }),
      ).toBeInTheDocument();
    });
  });
});
