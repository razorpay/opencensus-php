import React from 'react';
import { screen, server, render, waitForLoadingToFinish, userEvent, waitFor } from 'test-utils';
import App from 'merchant/views/Subscriptions/RegistrationLinks/List';
import { fetchRegistrationLinksMock } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/RegistrationLinks/List';
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

describe('Registration Links List', () => {
  beforeEach(async () => {
    server.use(fetchRegistrationLinksMock());
    render(<App location={{ search: '' }} />);
    await waitForLoadingToFinish('table-spinner');
  });

  test('Should render RL List Fields', () => {
    [
      'link id',
      'amount',
      'receipt',
      'registration link',
      'customer',
      'created at',
      'status',
    ].forEach((fieldLabel) => {
      expect(
        screen.getByRole('columnheader', {
          name: new RegExp(fieldLabel, 'i'),
        }),
      ).toBeInTheDocument();
    });
    expect(screen.getByText(/create new link/i)).toBeInTheDocument();
  });

  test('Should render RL List Field values', () => {
    expect(
      screen.getByRole('link', {
        name: /inv_l7tzvc3bp6fbt9/i,
      }),
    ).toBeInTheDocument();

    [
      'inv_l7tzvc3bp6fbt9',
      '^10$',
      '^676$',
      'https://rzp.io/i/sd8yhfm3tp',
      'email: ugparekh@gmail.com',
      'contact: 9821593603',
      'jan 24, 2023',
      'issued',
      'showing 1 - 1',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('should render RL Search Form', async () => {
    const searchBtn = screen.getByRole('button', { name: /search/i });
    const clearBtn = screen.getByRole('button', { name: /clear/i });

    await userEvent.click(clearBtn);
    await waitFor(() => {
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'clear',
        expect.objectContaining({
          eventStartLabel: 'registrationlink.search',
        }),
      );
    });
    await userEvent.click(searchBtn);
    await waitFor(() => {
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'initiate',
        expect.objectContaining({
          eventStartLabel: 'registrationlink.search',
        }),
      );
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'submit',
        expect.objectContaining({
          eventStartLabel: 'registrationlink.search',
        }),
      );
    });
  });
});
