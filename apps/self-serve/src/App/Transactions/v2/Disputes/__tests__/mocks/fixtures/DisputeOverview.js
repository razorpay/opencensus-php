import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import DisputeOverview from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeOverview';

export const renderApp = ({ session, mode = 'live' } = {}) => {
  const renderOutput = render(<DisputeOverview />, {
    initialState: {
      session: {
        mode,
        user: {
          merchant: {
            currency: 'INR',
          },
        },
        ...session,
      },
    },
  });
  return renderOutput;
};
