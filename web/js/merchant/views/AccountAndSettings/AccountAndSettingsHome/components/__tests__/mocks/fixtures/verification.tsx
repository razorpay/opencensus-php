import store from 'merchant/store';
import React from 'react';

jest.mock('merchant/views/Account/Profile/components/User2FASettings', () => () => (
  <div>
    <span>User 2FA Settings Module</span>
  </div>
));

export const state = store.getState();

export const getState = ({ userData = {} } = {}): Record<string, any> => ({
  session: {
    ...state.session,
    user: {
      ...state.session.user,
      is2FAMobileSignupEnabled: true,
      user: {
        ...state.session.user.user,
        org_enforced_second_factor_auth: false,
        signup_via_email: false,
      },
      findTag: () => false,
      ...userData,
    },
  },
});
