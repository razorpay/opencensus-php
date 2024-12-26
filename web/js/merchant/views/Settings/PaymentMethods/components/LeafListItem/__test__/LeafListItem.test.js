import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import { deepCopy } from 'common/utils/immutable';

import LeafListItem from 'merchant/views/Settings/PaymentMethods/components/LeafListItem';

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            enable_web_scrapper: {
              variables: {
                result: 'off',
              },
            },
          },
        }}
      />
    ),
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

const state = {
  session: {
    user: {
      live: true,
      isInstrumentRequestHidden: false,
    },
  },
  instrumentRequests: {
    leafInstrument: { name: 'Cards' },
    instrumentsTat: { 'pg.cards.domestic.amex': 12 },
  },
};

describe('LeafListItem', () => {
  const renderApp = ({ initialState = state, showModal = false, ...rest }) => {
    return render(<LeafListItem {...rest} />, { initialState, showModal });
  };

  describe('Request CTA', () => {
    test('should not render request CTA if merchant feature flag hide_instrument_request is set', () => {
      const instrument = { status: 'greyed' };
      const initialState = deepCopy(state);
      initialState.session.user.isInstrumentRequestHidden = true;

      renderApp({ initialState, instrument });

      const requestCTA = screen.queryByTestId('pm-request-cta');
      expect(requestCTA).not.toBeInTheDocument();
    });

    test('should render request CTA if merchant feature flag hide_instrument_request is not set', () => {
      const instrument = { status: 'greyed' };

      renderApp({ instrument });

      const requestCTA = screen.queryByTestId('pm-request-cta');
      expect(requestCTA).toBeInTheDocument();
      expect(requestCTA).toHaveTextContent('Request');
    });

    test('should open MissingInfoModal when collect_info property is present as instrument property', async () => {
      const instrument = {
        collect_info: [
          {
            display_name: 'Business Use Case',
            format: '',
            name: 'merchant_business_detail|pg_use_case',
            placeholder:
              'Add a brief business description and why you need this method. Please elaborate on your business category/Line Of Business ( Min 50 Chars)',
            type: 'textarea',
          },
        ],
        status: 'requestable',
        name: 'Amex Cards',
        path: 'pg.cards.domestic.amex',
      };

      renderApp({ instrument, showModal: true });

      const requestCTA = screen.queryByTestId('pm-request-cta');
      expect(requestCTA).toBeInTheDocument();

      await userEvent.click(requestCTA);

      const textArea = screen.getByPlaceholderText(instrument.collect_info[0].placeholder);
      expect(textArea).toBeInTheDocument();
    });

    test('should open confirmation alert box when collect_info property is not present as instrument property', async () => {
      const instrument = {
        status: 'requestable',
        name: 'Amex Cards',
        path: 'pg.cards.domestic.amex',
      };

      renderApp({ instrument });

      const requestCTA = screen.queryByTestId('pm-request-cta');
      expect(requestCTA).toBeInTheDocument();

      await userEvent.click(requestCTA);

      const confDialog = screen.getByText('Confirmation');
      expect(confDialog).toBeInTheDocument();
    });

    test('should keep buttons disabled if merchant is non-live', () => {
      const instrument = {
        status: 'requestable',
        name: 'Amex Cards',
        path: 'pg.cards.domestic.amex',
      };

      const newInitialState = deepCopy(state);
      newInitialState.session.user.live = false;

      renderApp({
        instrument,
        initialState: newInitialState,
      });

      const requestCTA = screen.queryByTestId('pm-request-cta');
      expect(requestCTA).toBeDisabled();
    });
  });

  describe('Link Account CTA', () => {
    test('should not render link account CTA if merchant feature flag hide_instrument_request is set and status is account_linkable', () => {
      const instrument = { status: 'account_linkable' };
      const initialState = deepCopy(state);
      initialState.session.user.isInstrumentRequestHidden = true;

      renderApp({ initialState, instrument });

      expect(screen.queryByTestId('pm-link-account-cta')).not.toBeInTheDocument();
    });

    test('should render link account CTA if merchant feature flag hide_instrument_request is not set and status is account_linkable', () => {
      const instrument = { status: 'account_linkable' };

      renderApp({ instrument });

      expect(screen.queryByTestId('pm-link-account-cta')).toBeInTheDocument();
      expect(screen.queryByTestId('pm-link-account-cta')).toHaveTextContent('Link Account');
    });

    test('should not render link account CTA if merchant feature flag hide_instrument_request is set and status is account_linkable', () => {
      const instrument = { status: 'account_linkable' };
      const initialState = deepCopy(state);
      initialState.session.user.isInstrumentRequestHidden = true;

      renderApp({ initialState, instrument });

      expect(screen.queryByTestId('pm-link-account-cta')).not.toBeInTheDocument();
    });
  });
});
