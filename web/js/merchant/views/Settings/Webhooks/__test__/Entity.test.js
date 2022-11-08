import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import WebhooksEntity from 'merchant/views/Settings/Webhooks/Entity';
import { screen, waitFor, fireEvent, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import { render } from '@testing-library/react';
import { Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import ModalDialog from 'common/ui/ModalDialog';
import { rest } from 'msw';
import {
  initialState,
  FakeMouseEvent,
} from 'merchant/views/Settings/Webhooks/__test__/mocks/fixtures/Entity';

describe('Webhooks - Entity.js', () => {
  const App = ({ state = initialState, ...rest }) => {
    const history = createMemoryHistory();
    return (
      <Provider store={storeWithInitialState(state)}>
        <ConfirmModalProvider>
          <Router history={history}>
            <>
              <ModalDialog />
              <WebhooksEntity {...rest} />
            </>
          </Router>
        </ConfirmModalProvider>
      </Provider>
    );
  };

  test('should render webhook entity page title', async () => {
    render(<App state={initialState} id="KXzYQUueHG0GmW" />);
    await waitFor(() => {
      const pageTitle = screen.getByText(/Webhook Details/i);
      expect(pageTitle).toBeInTheDocument();
    });
  });

  test('should render webhook entity page fields', async () => {
    render(<App state={initialState} id="KXzYQUueHG0GmW" />);
    await waitFor(() => {
      expect(screen.getByText(/Webhook URL/i)).toBeInTheDocument();
      expect(screen.getByText(/status/i)).toBeInTheDocument();
    });
  });

  test('should render webhook entity page CTAs', async () => {
    render(<App state={initialState} id="KXzYQUueHG0GmW" />);
    await waitFor(() => {
      expect(screen.getByText(/Delete/i)).toBeInTheDocument();
      expect(screen.getByText(/Edit/i)).toBeInTheDocument();
    });
  });

  test('should disable webhook on toggle click', async () => {
    render(<App state={initialState} id="KXzYQUueHG0GmW" />);
    let switchBtn = null;
    await waitFor(() => {
      switchBtn = screen.getByTestId('webhook-toggle-switch');
      expect(switchBtn).toBeInTheDocument();
    });
    fireEvent(
      switchBtn,
      new FakeMouseEvent('click', {
        bubbles: true,
        pageX: 350,
        pageY: 125,
      }),
    );
    await waitFor(() => {
      expect(screen.getByText(/Disabled/i)).toBeInTheDocument();
    });
  });

  test('should not disable webhook on toggle click fail', async () => {
    server.use(
      rest.put('*/merchant/api/test/webhooks/:id', (req, res, ctx) => {
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );
    render(<App state={initialState} id="KXzYQUueHG0GmW" />);
    let switchBtn = null;
    await waitFor(() => {
      switchBtn = screen.getByTestId('webhook-toggle-switch');
      expect(switchBtn).toBeInTheDocument();
    });
    fireEvent(
      switchBtn,
      new FakeMouseEvent('click', {
        bubbles: true,
        pageX: 350,
        pageY: 125,
      }),
    );
    await waitFor(() => {
      expect(screen.getByText(/Enabled/i)).toBeInTheDocument();
    });
  });

  test('should open delete confirmation modal on delete click', async () => {
    render(<App state={initialState} id="KXzYQUueHG0GmW" />, { showModal: true });
    await waitFor(() => {
      const deleteBtn = screen.getByText(/Delete/i);
      fireEvent.click(deleteBtn);
      expect(screen.getByText(/Are you sure?/i)).toBeInTheDocument();
      expect(
        screen.getByText(/You are about to permanently delete the webhook URL.?/i),
      ).toBeInTheDocument();
    });
  });

  test('should open edit modal on edit click', async () => {
    render(<App state={initialState} id="KXzYQUueHG0GmW" />, { showModal: true });
    let editBtn;
    await waitFor(() => {
      editBtn = screen.getByText('Edit');
      expect(editBtn).toBeInTheDocument();
    });
    fireEvent.click(editBtn);
    await waitFor(() => {
      expect(screen.getByText('Webhook Setup')).toBeInTheDocument();
    });
  });

  test('should delete webhook on delete click', async () => {
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
    render(<App state={initialState} id="KXzYQUueHG0GmW" />, { showModal: true });
    let deleteCTA;
    await waitFor(() => {
      const deleteBtn = screen.getByText(/Delete/i);
      fireEvent.click(deleteBtn);
      expect(screen.getByText(/Are you sure?/i)).toBeInTheDocument();
      expect(
        screen.getByText(/You are about to permanently delete the webhook URL.?/i),
      ).toBeInTheDocument();
      deleteCTA = screen.getByText('Yes, Delete');
      expect(deleteCTA).toBeInTheDocument();
    });
    fireEvent.click(deleteCTA);
    await waitFor(() => {
      expect(screen.queryByText(/Deleting.../i)).not.toBeInTheDocument();
    });
  });
});
