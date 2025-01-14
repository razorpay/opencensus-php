import React from 'react';
import { screen, server, render, waitForLoadingToFinish, userEvent, waitFor } from 'test-utils';
import App from 'merchant/views/Subscriptions/Plans/List';
import { fetchPlans } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/fixtures';
import * as analytics from 'merchant/views/Subscriptions/analytics';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('merchant/views/Subscriptions/analytics', () => ({
  ...jest.requireActual('merchant/views/Subscriptions/analytics'),
  track: jest.fn(),
}));
jest.mock('merchant/components/ShowWhen', () => ({
  ...jest.requireActual('merchant/components/ShowWhen'),
  __esModule: true,
  default: ({ children, additionalCondition }) => (
    // eslint-disable-next-line react/no-unknown-property
    <div additionalCondition={additionalCondition.bind({}, true)}>{children}</div>
  ),
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

describe('Plans List', () => {
  let analyticsSpy;
  beforeEach(async () => {
    window.rzpQ = {
      interaction: jest.fn(),
    };
    analyticsSpy = jest.spyOn(analytics, 'track');
    server.use(fetchPlans());
    render(<App location={{ search: '' }} docUrl="https://razorpay.com/docs/subscriptions/" />, {
      initialState: {
        session: {
          user: {
            isAllowedEdit: () => true,
          },
        },
      },
    });
    await waitForLoadingToFinish('table-spinner');
  });
  test('Should render plan list fields', () => {
    [('plan id', 'plan name', 'amount/unit', 'billing cycle', 'created at')].forEach(
      (fieldLabel) => {
        expect(
          screen.getByRole('columnheader', {
            name: new RegExp(fieldLabel, 'i'),
          }),
        ).toBeInTheDocument();
      },
    );
  });

  // TODO: fix this test case
  test.skip('Should render plan list field values', () => {
    expect(
      screen.getByRole('link', {
        name: /plan_kovo2tp4ewkjlf/i,
      }),
    ).toBeInTheDocument();
    ['test-plan', '^1$', 'every year', '07 dec 2022, 07:05:10 am'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should render Plan Search Form', async () => {
    const searchBtn = screen.getByRole('button', { name: /search/i });
    const clearBtn = screen.getByRole('button', { name: /clear/i });

    await userEvent.click(clearBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('plan.search.clear');
    });
    await userEvent.click(searchBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('plan.search.submit');
    });

    const docLink = screen.getByRole('link', {
      name: /documentation/i,
    });
    await userEvent.click(docLink);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('plan.search.documentation');
    });

    const takeTourBtn = screen.getByRole('button', {
      name: /take a tour/i,
    });
    await userEvent.click(takeTourBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('plan.search.help');
    });
  });

  test('Should create new plan', async () => {
    const newPlan = screen.getByRole('button', {
      name: /new plan/i,
    });
    await userEvent.click(newPlan);
    expect(analyticsSpy).toHaveBeenCalledWith('plan.create.initiate');
  });
});
