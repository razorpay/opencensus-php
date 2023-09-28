import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import WebhooksEntity from 'merchant/views/Settings/Webhooks/Entity';
import { screen, waitFor, fireEvent, server, render } from 'test-utils';
import { rest } from 'msw';
import {
  initialState,
  FakeMouseEvent,
} from 'merchant/views/Settings/Webhooks/__test__/mocks/fixtures/Entity';

describe('Webhooks - Entity.js', () => {
  test('should render webhook entity page title', async () => {
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
    });
    await waitFor(() => {
      const pageTitle = screen.getByText(/Webhook Details/i);
      expect(pageTitle).toBeInTheDocument();
    });
  });

  test('should render webhook entity page fields', async () => {
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
    });
    await waitFor(() => {
      expect(screen.getByText(/Webhook URL/i)).toBeInTheDocument();
      expect(screen.getByText(/status/i)).toBeInTheDocument();
    });
  });

  test('should render webhook entity page CTAs', async () => {
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
    });
    await waitFor(() => {
      expect(screen.getByText(/Delete/i)).toBeInTheDocument();
      expect(screen.getByText(/Edit/i)).toBeInTheDocument();
    });
  });

  test('should disable webhook on toggle click', async () => {
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
    });
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
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
    });
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
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
      showModal: true,
    });
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
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
      showModal: true,
    });
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
    render(<WebhooksEntity id="KXzYQUueHG0GmW" />, {
      initialState,
      showModal: true,
    });
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
