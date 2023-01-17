import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { App, onboarding } from 'merchant/views/Affordability/__test__/mocks/onboarding';
import { render, screen, userEvent } from 'test-utils';
import { MemoryRouter } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import { defaultProps } from './mocks/fixtures';

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

  const renderApp = (props = {}) => {
    render(<App {...props} history={history} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true } },
      },
    });
  };

  test('should render Onboarding component without error', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render step 1 screen', async () => {
    render(
      <MemoryRouter initialEntries={['/affordability/widget/']}>
        <App history={history} />
      </MemoryRouter>,
      {
        initialState: {
          session: { user: { isAffordabilityWidgetEnabled: true } },
        },
      },
    );
    expect(screen.getByText('Benefits of the widget')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Continue'));

    expect(history.push).toHaveBeenCalledTimes(1);
  });

  test('should show all the widget enabled providers', async () => {
    render(
      <MemoryRouter initialEntries={['/affordability/widget/platforms']}>
        <App history={history} />
      </MemoryRouter>,
      {
        initialState: {
          session: { user: { isAffordabilityWidgetEnabled: true } },
        },
      },
    );
    expect(screen.getByText('Choose your website platform')).toBeInTheDocument();
    await userEvent.click(screen.getByText('WooCommerce'));

    expect(history.push).toHaveBeenCalledTimes(1);
  });

  test('should show the widget enablement screen', async () => {
    render(
      <MemoryRouter initialEntries={['/affordability/widget/setup/others']}>
        <App history={history} {...defaultProps} {...onboarding} />
      </MemoryRouter>,
      {
        initialState: {
          session: { user: { isAffordabilityWidgetEnabled: true } },
        },
      },
    );
    expect(screen.getByText('Set up Affordability Widget')).toBeInTheDocument();
    await userEvent.click(screen.getAllByText('Enable Widget')[1]);
  });
});
