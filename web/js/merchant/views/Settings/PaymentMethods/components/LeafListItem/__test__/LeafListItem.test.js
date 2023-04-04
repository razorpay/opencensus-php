import React from 'react';
import { Provider } from 'react-redux';
import { render, screen } from 'test-utils';

import { deepCopy } from 'common/utils/immutable';
import { storeWithInitialState } from 'merchant/store';

import LeafListItem from 'merchant/views/Settings/PaymentMethods/components/LeafListItem';

const state = {
  session: {
    user: {
      isInstrumentRequestHidden: false,
    },
  },
};

describe('LeafListItem', () => {
  const App = ({ initialState = state, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <LeafListItem {...rest} />
      </Provider>
    );
  };

  describe('Request CTA', () => {
    test('should not render request CTA if merchant feature flag hide_instrument_request is set', () => {
      const instrument = { status: 'greyed' };
      const initialState = deepCopy(state);
      initialState.session.user.isInstrumentRequestHidden = true;

      render(<App initialState={initialState} instrument={instrument} />);

      expect(screen.queryByTestId('pm-request-cta')).not.toBeInTheDocument();
    });

    test('should render request CTA if merchant feature flag hide_instrument_request is not set', () => {
      const instrument = { status: 'greyed' };

      render(<App instrument={instrument} />);

      expect(screen.queryByTestId('pm-request-cta')).toBeInTheDocument();
      expect(screen.queryByTestId('pm-request-cta')).toHaveTextContent('Request');
    });
  });

  describe('Link Account CTA', () => {
    test('should not render link account CTA if merchant feature flag hide_instrument_request is set and status is account_linkable', () => {
      const instrument = { status: 'account_linkable' };
      const initialState = deepCopy(state);
      initialState.session.user.isInstrumentRequestHidden = true;

      render(<App initialState={initialState} instrument={instrument} />);

      expect(screen.queryByTestId('pm-link-account-cta')).not.toBeInTheDocument();
    });

    test('should render link account CTA if merchant feature flag hide_instrument_request is not set and status is account_linkable', () => {
      const instrument = { status: 'account_linkable' };

      render(<App instrument={instrument} />);

      expect(screen.queryByTestId('pm-link-account-cta')).toBeInTheDocument();
      expect(screen.queryByTestId('pm-link-account-cta')).toHaveTextContent('Link Account');
    });

    test('should not render link account CTA if merchant feature flag hide_instrument_request is set and status is account_linkable', () => {
      const instrument = { status: 'account_linkable' };
      const initialState = deepCopy(state);
      initialState.session.user.isInstrumentRequestHidden = true;

      render(<App initialState={initialState} instrument={instrument} />);

      expect(screen.queryByTestId('pm-link-account-cta')).not.toBeInTheDocument();
    });
  });
});
