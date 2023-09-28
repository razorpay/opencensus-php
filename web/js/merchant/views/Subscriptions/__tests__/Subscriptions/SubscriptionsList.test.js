import React from 'react';
import { screen, server, render, waitForLoadingToFinish, userEvent, waitFor } from 'test-utils';
import App from 'merchant/views/Subscriptions/Subscriptions/List';
import {
  fetchSubscriptions,
  fetchSubscriptionsOverview,
  fetchSubscriptionsOverviewError,
} from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/fixtures';
import analytics from 'merchant/views/Subscriptions/analytics';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('merchant/views/Subscriptions/analytics', () => ({
  ...jest.requireActual('merchant/views/Subscriptions/analytics'),
  track: jest.fn(),
}));
jest.mock('merchant/components/ListFilter', () => ({
  ...jest.requireActual('merchant/components/ListFilter'),
  __esModule: true,
  default: ({ onSearchAnalytics, onClearAnalytics }) => (
    <div>
      <button type="submit" onClick={onSearchAnalytics.bind({}, { submit: true })}>
        Search
      </button>
      <button onClick={onClearAnalytics}>Clear</button>
    </div>
  ),
}));
jest.mock('merchant/components/QuickGuide/TakeATourButton', () => ({
  ...jest.requireActual('merchant/components/QuickGuide/TakeATourButton'),
  __esModule: true,
  default: ({ onClick }) => <button onClick={onClick}>Take a tour</button>,
}));

describe('Subscriptions List', () => {
  let analyticsSpy;

  beforeEach(async () => {
    const initialState = {
      session: {
        user: {
          isSubscriptionExpiryEnabled: true,
          isOrgAllowedFunctionality: () => true,
          findTag: () => false,
        },
      },
    };
    window.rzpQ = {
      interaction: jest.fn(),
    };
    server.use(fetchSubscriptions());
    analyticsSpy = jest.spyOn(analytics, 'track');

    render(<App location={{ search: '' }} docUrl="/123" />, {
      initialState,
    });
    await waitForLoadingToFinish();
  });

  afterEach(() => {
    analyticsSpy.mockClear();
  });

  test('Should render Subscriptions List Fields', () => {
    [
      'subscription id',
      'plan id',
      'subscription link',
      'customer id',
      'next due on',
      'created at',
      'status',
    ].forEach((fieldLabel) => {
      expect(
        screen.getByRole('columnheader', {
          name: new RegExp(fieldLabel, 'i'),
        }),
      ).toBeInTheDocument();
    });
  });

  // TODO: fix this failing test
  test.skip('Should render Subscriptions List Field values', () => {
    ['sub_kp2sijihd0j5fg', 'plan_kovo2tp4ewkjlf'].forEach((fieldLabel) => {
      expect(
        screen.getByRole('link', {
          name: new RegExp(fieldLabel, 'i'),
        }),
      ).toBeInTheDocument();
    });

    ['https://rzp.io/i/gfrzeisobn', 'cust_123', '07 dec 2022, 01:35:50 pm', '^created$'].forEach(
      (fieldLabel) => {
        expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
      },
    );
  });

  test('Should render Subscriptions Search Form', async () => {
    const searchBtn = screen.getByRole('button', { name: /search/i });
    const clearBtn = screen.getByRole('button', { name: /clear/i });

    await userEvent.click(clearBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.search.clear');
    });
    await userEvent.click(searchBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.search.submit');
    });
  });

  test('Should render Subscriptions Header', async () => {
    const docLink = screen.getByRole('link', {
      name: /documentation/i,
    });
    await userEvent.click(docLink);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.search.documentation');
    });

    const takeTourBtn = screen.getByRole('button', {
      name: /take a tour/i,
    });
    await userEvent.click(takeTourBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.search.help');
    });
    const newPlan = screen.getByRole('link', {
      name: /create new subscription/i,
    });
    await userEvent.click(newPlan);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.create.initiate');
    });
  });
});

describe('Subscriptions Fetch with isSubscriptionExpiryEnabled enabled', () => {
  let initialState, analyticsSpy;
  beforeEach(() => {
    window.rzpQ = {
      interaction: jest.fn(),
    };
    initialState = {
      session: {
        user: {
          isSubscriptionExpiryEnabled: true,
          isOrgAllowedFunctionality: () => true,
          findTag: () => false,
        },
      },
    };
    analyticsSpy = jest.spyOn(analytics, 'track');

    server.use(fetchSubscriptions());
  });
  afterEach(() => {
    analyticsSpy.mockClear();
  });

  test('Should not render subscriptions and show error', async () => {
    server.use(fetchSubscriptionsOverviewError());
    render(<App location={{ search: '' }} docUrl="/123" />, {
      initialState,
    });
    await waitForLoadingToFinish();
    expect(screen.getByText('Something went wrong. Please try again.')).toBeInTheDocument();
  });

  test('Should render all subscriptions with applied filters', async () => {
    server.use(fetchSubscriptionsOverview());

    const { container } = render(<App location={{ search: '' }} docUrl="/123" />, {
      initialState,
    });
    await waitForLoadingToFinish();
    const cards = container.getElementsByClassName('card');

    expect(cards[0]).toHaveTextContent(/0active subscriptions/i);
    await userEvent.click(cards[0]);
    expect(analyticsSpy).toHaveBeenCalledWith('subscription.filter.active');
    expect(cards[1]).toHaveTextContent(/0halted subscriptions/i);
    await userEvent.click(cards[1]);
    expect(analyticsSpy).toHaveBeenCalledWith('subscription.filter.halted');
    expect(cards[2]).toHaveTextContent(/0subscriptions completing in 7 days/i);
    await userEvent.click(cards[2]);
    expect(analyticsSpy).toHaveBeenCalledWith('subscription.filter.comp_7_days');
    expect(cards[3]).toHaveTextContent(/0subscriptions with cards expiring in 7 days/i);
    await userEvent.click(cards[3]);
    expect(analyticsSpy).toHaveBeenCalledWith('subscription.filter.exp_7_days');
  });
});
