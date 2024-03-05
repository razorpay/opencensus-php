import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import PaymentsContainer from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsContainer';

export const renderApp = (props = {}, path = '/', initialEntries = ['/']) => {
  return render(<PaymentsContainer location={{ pathname: '/' }} {...props} />, {
    renderViaRouteGuard: false,
    path,
    initialEntries,
    initialState: {
      session: {
        user: {
          isOmniChannelMerchant: false,
        },
      },
    },
  });
};
