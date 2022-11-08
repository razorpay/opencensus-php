import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import AddEditWebhook from 'merchant/views/Settings/Webhooks/AddEditWebhook';
import { render, screen, waitFor, fireEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import {
  initialState,
  webhook,
} from 'merchant/views/Settings/Webhooks/__test__/mocks/fixtures/AddEditWebhook';

describe('Webhooks - AddEditWebhook.js', () => {
  const App = ({ state = initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(state)}>
        <AddEditWebhook {...rest} />
      </Provider>
    );
  };

  test('should render add/edit webhook form correctly', async () => {
    render(<App state={initialState} />);
    await waitFor(() => {
      expect(screen.getByText('Webhook Setup')).toBeInTheDocument();
      expect(screen.getByText('Active Events')).toBeInTheDocument();
      expect(screen.getByText('Webhook URL')).toBeInTheDocument();
      expect(screen.getByText('Secret')).toBeInTheDocument();
    });
  });

  test('should render add/edit webhook form CTAs', async () => {
    render(<App state={initialState} />);
    await waitFor(() => {
      expect(screen.getByText('Cancel')).toBeInTheDocument();
      expect(screen.getByText('Create Webhook')).toBeInTheDocument();
    });
  });

  test('should show error message when url is not entered', async () => {
    render(<App state={initialState} />);
    let urlInput, createBtn;
    await waitFor(() => {
      urlInput = screen.getByTestId('webhook-url');
      createBtn = screen.getByText('Create Webhook');

      expect(urlInput).toBeInTheDocument();
      expect(createBtn).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });
    fireEvent.change(urlInput, { target: { value: 'https://www.reddit.com/r/Cricket/' } });
    fireEvent.click(createBtn);

    await waitFor(() => {
      expect(screen.getByText('Required')).toBeInTheDocument();
    });
  });

  test('should create webhook successfully on valid input', async () => {
    render(<App state={initialState} />);
    let urlInput, createBtn;
    await waitFor(() => {
      urlInput = screen.getByTestId('webhook-url');
      createBtn = screen.getByText('Create Webhook');

      expect(urlInput).toBeInTheDocument();
      expect(createBtn).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });
    const wehbhookEvents = screen.getByTestId(`eventGroup['order']`);
    expect(wehbhookEvents).toBeInTheDocument();

    fireEvent.change(urlInput, { target: { value: 'https://www.reddit.com/r/Cricket/' } });
    fireEvent.click(wehbhookEvents);
    fireEvent.click(createBtn);

    await waitFor(() => {
      expect(screen.queryByText('Saving...')).toBeInTheDocument();
    });
  });

  test('should edit webhook successfully on valid input', async () => {
    render(<App state={initialState} webhook={webhook} />);
    let urlInput, saveBtn;
    await waitFor(() => {
      urlInput = screen.getByTestId('webhook-url');
      saveBtn = screen.getByText('Save Webhook');

      expect(urlInput).toBeInTheDocument();
      expect(saveBtn).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });
    fireEvent.change(urlInput, { target: { value: 'https://www.reddit.com/r/test/' } });
    fireEvent.click(saveBtn);

    await waitFor(() => {
      expect(screen.queryByText('Saving...')).toBeInTheDocument();
    });
  });

  test('should throw error on duplicate url', async () => {
    render(<App state={initialState} webhookList={initialState.webhooks.webhooks} />);
    let urlInput, createBtn;
    await waitFor(() => {
      urlInput = screen.getByTestId('webhook-url');
      createBtn = screen.getByText('Create Webhook');

      expect(urlInput).toBeInTheDocument();
      expect(createBtn).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });

    fireEvent.change(urlInput, { target: { value: 'https://twitter.com/' } });
    fireEvent.click(createBtn);

    await waitFor(() => {
      expect(screen.getByText('Webhook URL already exists')).toBeInTheDocument();
    });
  });

  test('should show/hide secret on cta click', async () => {
    render(<App state={initialState} webhookList={initialState.webhooks.webhooks} />);
    let toggleCTa;
    await waitFor(() => {
      toggleCTa = screen.getByTestId('toggle-secret');

      expect(toggleCTa).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });
    expect(screen.getByText('Show Secret')).toBeInTheDocument();
    fireEvent.click(toggleCTa);
    expect(screen.getByText('Hide Secret')).toBeInTheDocument();
  });

  test('should search successfully for a webhook event', async () => {
    render(<App state={initialState} webhookList={initialState.webhooks.webhooks} />);
    let searchInput;
    await waitFor(() => {
      searchInput = screen.getByTestId('webhook-events-search');

      expect(searchInput).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });
    fireEvent.change(searchInput, { target: { value: 'order' } });
    expect(screen.getByTestId('webhook-events-search-close')).toBeInTheDocument();
    expect(screen.queryByTestId(`eventGroup['refund']`)).not.toBeInTheDocument();
    expect(screen.queryByTestId(`eventGroup['order']`)).toBeInTheDocument();
  });

  test('should reset events on search close click', async () => {
    render(<App state={initialState} webhookList={initialState.webhooks.webhooks} />);
    let searchInput;
    await waitFor(() => {
      searchInput = screen.getByTestId('webhook-events-search');

      expect(searchInput).toBeInTheDocument();
      expect(screen.queryByTestId('webhook-events-spinner')).not.toBeInTheDocument();
    });
    fireEvent.change(searchInput, { target: { value: 'order' } });
    const searchReset = screen.getByTestId('webhook-events-search-close');
    expect(searchReset).toBeInTheDocument();

    fireEvent.click(searchReset);
    expect(screen.queryByTestId(`eventGroup['refund']`)).toBeInTheDocument();
    expect(screen.queryByTestId(`eventGroup['payment']`)).toBeInTheDocument();
  });
});
