import React from 'react';
import { screen, server, render, waitForLoadingToFinish, userEvent, waitFor } from 'test-utils';
import App from 'merchant/views/Subscriptions/Tokens/List';
import { fetchTokens } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/Tokens';
import { trackSearchEvent } from 'merchant/views/Subscriptions/utils';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('merchant/views/Subscriptions/utils', () => ({
  ...jest.requireActual('merchant/views/Subscriptions/utils'),
  trackSearchEvent: jest.fn(),
}));
jest.mock('common/utils/validators', () => ({
  ...jest.requireActual('common/utils/validators'),
  trimDeep: jest.fn(),
}));
jest.mock('merchant/components/ListFilter', () => ({
  ...jest.requireActual('merchant/components/ListFilter'),
  __esModule: true,
  default: ({ onSubmit, onClearAnalytics }) => (
    <div>
      <button onClick={onSubmit.bind({}, 'submit')}>Search</button>
      <button onClick={onClearAnalytics}>Clear</button>
    </div>
  ),
}));

describe('Tokens List', () => {
  beforeEach(async () => {
    server.use(fetchTokens());
    render(<App location={{ search: '' }} />);
    await waitForLoadingToFinish();
  });
  test('Should render RL List Fields', () => {
    ['token id', 'method', 'email', 'contact', 'created at', 'status'].forEach((fieldLabel) => {
      expect(
        screen.getByRole('columnheader', {
          name: new RegExp(fieldLabel, 'i'),
        }),
      ).toBeInTheDocument();
    });
  });

  // TODO: fix this failing test
  test.skip('Should render RL List Field values', () => {
    expect(
      screen.getByRole('link', {
        name: /token_l7zpw1go48yduo/i,
      }),
    ).toBeInTheDocument();
    [
      'upi',
      'testing@testing.com',
      '1234567890',
      '24 jan 2023, 10:57:46 am',
      'initiated',
      'showing 1 - 1',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should render RL Token Search Form', async () => {
    const searchBtn = screen.getByRole('button', { name: /search/i });
    const clearBtn = screen.getByRole('button', { name: /clear/i });

    await userEvent.click(clearBtn);
    await waitFor(() => {
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'clear',
        expect.objectContaining({
          eventStartLabel: 'token.search',
        }),
      );
    });
    await userEvent.click(searchBtn);
    await waitFor(() => {
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'initiate',
        expect.objectContaining({
          eventStartLabel: 'token.search',
        }),
      );
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'submit',
        expect.objectContaining({
          eventStartLabel: 'token.search',
        }),
      );
    });
  });
});
