import React from 'react';

import { render, screen } from 'common/services/test/test-utils';
import ApplicationNew from 'merchant/views/Settings/Applications/new';

jest.mock('common/splitz', () => ({
  __esModule: true,
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            partnerships_oauth_phantom_configurator: { variables: { result: 'on' } },
          },
        }}
      />
    ),
}));

const isPartner = jest.fn();
const state = {
  session: {
    user: {
      isPartner,
    },
  },
};

describe('Application New', () => {
  const renderApp = () => {
    render(<ApplicationNew />, {
      initialState: state,
      initialEntries: [
        {
          pathname: '/partners/applications/test-id',
          params: { id: 'test-id' },
        },
      ],
      path: '/partners/applications/:id',
    });
  };

  test('should render applicationForm', () => {
    renderApp();
    expect(screen.getByText('Onboarding UI Configurator')).toBeInTheDocument();
    expect(screen.getByText('Name')).toBeInTheDocument();
  });
});
