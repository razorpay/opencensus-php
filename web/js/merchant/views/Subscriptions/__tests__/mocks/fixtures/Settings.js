import React from 'react';
import App from 'merchant/views/Subscriptions/Settings/index';
import { render } from 'test-utils';
import { rest } from 'msw';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('common/ui/Forms/SwitchField', () => (props) => {
  return <button onClick={(e) => props.onChange(e, jest.fn())}>{props.children}</button>;
});
jest.mock('merchant/views/Subscriptions/analytics', () => ({
  track: jest.fn(),
}));

export const saveErrorMockSettings = {
  status_code: 400,
  success: false,
  errors: ['Bad Request'],
};

const subscriptionsSettingsError = {
  loading: false,
  items: [],
  error: null,
  settings: {
    error: true,
    errors: ['Unable to fetch settings'],
  },
};

export const subscriptions = {
  loading: false,
  items: [],
  error: null,
  settings: {
    entity: 'collection',
    count: 3,
    items: [
      {
        id: 'HoWIEO0kWGvAci',
        setting_enabled: '1',
        name: 'card',
      },
      {
        id: 'HoWIEOqe2HvbAg',
        setting_enabled: '1',
        name: 'upi',
      },
      {
        id: 'K1SGd8TO3ShCsl',
        setting_enabled: '1',
        name: 'emandate',
      },
    ],
  },
  offers: {
    loading: true,
    items: [],
  },
};

export const renderInitialApp = () => {
  return render(<App />);
};

export const renderAppWithError = (props = {}) => {
  return render(<App {...props} />, {
    initialState: {
      session: {
        user: {
          isEmandateOnSubscriptionEnabled: true,
          isOrgAllowedFunctionality: () => true,
          findTag: () => false,
        },
        org: {
          custom_code: 'rzp',
        },
      },
      subscriptions: subscriptionsSettingsError,
    },
  });
};

export const renderApp = (props = {}) => {
  return render(<App {...props} />, {
    initialState: {
      session: {
        user: {
          isEmandateOnSubscriptionEnabled: true,
          isOrgRZP: true,
          isOrgAllowedFunctionality: () => true,
          findTag: () => false,
        },
        org: {
          custom_code: 'rzp',
        },
      },
      subscriptions,
    },
  });
};

export const renderAppWithoutEmandate = (props = {}) => {
  return render(<App {...props} />, {
    initialState: {
      session: {
        user: {
          isEmandateOnSubscriptionEnabled: false,
          isOrgAllowedFunctionality: () => true,
          findTag: () => false,
        },
        org: {
          custom_code: 'rzp',
        },
      },
      subscriptions,
    },
  });
};

export const fetchSettings = () => {
  const url = '*/merchant/api/*/subscriptions/settings';
  return rest.get(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          entity: 'collection',
          count: 3,
          items: [
            { id: 'HoWIEO0kWGvAci', setting_enabled: '1', name: 'card' },
            { id: 'HoWIEOqe2HvbAg', setting_enabled: '1', name: 'upi' },
            { id: 'K1SGd8TO3ShCsl', setting_enabled: '1', name: 'emandate' },
          ],
        },
      }),
      ctx.delay(50),
    );
  });
};

export const saveSettings = () => {
  const url = '*/merchant/api/test/subscriptions/settings';
  return rest.post(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: { id: 'HoWIEOqe2HvbAg', setting_enabled: '0', name: 'card' },
      }),
      ctx.delay(50),
    );
  });
};

export const saveSettingsError = () => {
  const url = '*/merchant/api/test/subscriptions/settings';
  return rest.post(url, (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 400,
        success: false,
        errors: ['Bad Request'],
      }),
      ctx.delay(50),
    );
  });
};
