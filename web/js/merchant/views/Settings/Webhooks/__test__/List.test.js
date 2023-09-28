import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import WebhooksContainer from 'merchant/views/Settings/Webhooks/List';
import { screen, waitFor, fireEvent, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { rest } from 'msw';
import { render } from '@testing-library/react';
import { Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import ModalDialog from 'common/ui/ModalDialog';
import * as ListFixtures from 'merchant/views/Settings/Webhooks/__test__/mocks/fixtures/List';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);

describe('Webhooks - List.js', () => {
  const App = ({ state = ListFixtures.initialState, ...rest }) => {
    const history = createMemoryHistory();
    return (
      <Provider store={storeWithInitialState(state)}>
        <Router navigator={history} location={history.location}>
          <>
            <ModalDialog />
            <WebhooksContainer {...rest} />
          </>
        </Router>
      </Provider>
    );
  };

  test('should render webhook landing header correctly', () => {
    server.use(
      rest.get('*/merchant/api/test/webhooks?skip=0&count=25', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: {
              entity: 'collection',
              count: 0,
              items: [],
            },
          }),
          ctx.delay(50),
        );
      }),
    );
    render(<App state={ListFixtures.initialState} location={ListFixtures.location} />);
    expect(screen.getByText('+ Add New Webhook')).toBeInTheDocument();
    expect(screen.getByText('Documentation')).toBeInTheDocument();
  });

  test('should open add/edit webhook form on CTA click', async () => {
    server.use(
      rest.get('*/merchant/api/test/webhooks?skip=0&count=25*', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: {
              entity: 'collection',
              count: 0,
              items: [],
            },
          }),
          ctx.delay(50),
        );
      }),
    );
    render(<App state={ListFixtures.initialState} location={ListFixtures.location} />);
    const CTA = screen.getByRole('button', {
      name: /Add New Webhook/i,
    });
    fireEvent.click(CTA);
    await waitFor(() => {
      expect(screen.getByText('Webhook Setup')).toBeInTheDocument();
    });
  });

  test('should render error alert if error(s) exist', async () => {
    server.use(
      rest.get('*/merchant/api/test/webhooks?skip=0&count=25', (req, res, ctx) => {
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );
    const { container } = render(
      <App state={ListFixtures.initialState} location={ListFixtures.location} />,
    );
    await waitFor(() => {
      expect(container.getElementsByClassName('alert').length).toBe(1);
    });
  });
});
