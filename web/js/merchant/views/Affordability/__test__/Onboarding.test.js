import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { App, onboarding } from 'merchant/views/Affordability/__test__/mocks/onboarding';
import { render, screen, userEvent } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';

import { render as mainRender } from '@testing-library/react';
import { MemoryRouter, Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import { defaultProps } from './mocks/fixtures';
import { Provider } from 'react-redux';

let history;
describe('Affordability self serve onboarding screen', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.rzp_user = {};
    history = createMemoryHistory();
    history.push = jest.fn();
    window.rzpQ = {
      component: jest.fn(),
      productOnboarding: () => ({
        success: jest.fn(),
      }),
    };
  });

  const renderApp = (props = {}, config = {}) => {
    render(<App {...props} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true } },
      },
      history,
      ...config,
    });
  };

  test('should render Onboarding component without error', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render step 1 screen', async () => {
    renderApp();
    expect(screen.getByText('Benefits of the widget')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Continue'));

    expect(history.push).toHaveBeenCalledTimes(1);
  });

  test('should show all the widget enabled providers', async () => {
    const history = createMemoryHistory({
      initialEntries: ['/platforms'],
    });
    history.push = jest.fn();
    mainRender(
      <Provider
        store={storeWithInitialState({
          session: { user: { isAffordabilityWidgetEnabled: true } },
        })}
      >
        <Router location={history.location} navigator={history}>
          <App history={history} {...defaultProps} {...onboarding} />
        </Router>
      </Provider>,
    );
    expect(screen.getByText('Choose your website platform')).toBeInTheDocument();
    await userEvent.click(screen.getByText('WooCommerce'));

    expect(history.push).toHaveBeenCalledTimes(1);
  });

  test('should show the widget enablement screen', async () => {
    mainRender(
      <Provider
        store={storeWithInitialState({
          session: { user: { isAffordabilityWidgetEnabled: true } },
        })}
      >
        <MemoryRouter initialEntries={['/setup/others']}>
          <App history={history} {...defaultProps} {...onboarding} />
        </MemoryRouter>
      </Provider>,
    );
    expect(screen.getByText('Set up Affordability Widget')).toBeInTheDocument();
    await userEvent.click(screen.getAllByText('Enable Widget')[1]);
  });
});
