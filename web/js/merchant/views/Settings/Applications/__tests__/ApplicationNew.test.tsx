import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import ApplicationNew from 'merchant/views/Settings/Applications/new';

jest.mock('common/splitz', () => ({
  __esModule: true,
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{ abExperiments: { Partnerships_oauth_phantom: { variables: { result: 'on' } } } }}
      />
    ),
}));

jest.mock('merchant/components/ShowWhen', () => ({
  __esModule: true,
  default: ({ children }) => <div>{children}</div>,
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
    render(
      <ApplicationNew
        splitz={{ abExperiments: { Partnerships_oauth_phantom: { variables: { result: 'on' } } } }}
      />,
      {
        initialState: state,
        historyOptions: {
          initialEntries: [
            {
              pathname: '/partners/applications/test-id',
              params: { id: 'test-id' },
            },
          ],
        },
        path: '/partners/applications/:id',
      },
    );
  };

  // TODO: fix this test case
  test.skip('should render applicationForm', () => {
    renderApp();
    expect(screen.getByText('Onboarding UI Configurator')).toBeInTheDocument();
    expect(screen.getByText('Name')).toBeInTheDocument();
  });
});
