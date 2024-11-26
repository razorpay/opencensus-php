import React from 'react';
import store from 'merchant/store';

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile',
  () => (): JSX.Element => {
    return (
      <div>
        <span>Merchant Profile</span>
      </div>
    );
  },
);

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/AccountAndProductSection',
  () =>
    ({ sections }): JSX.Element => {
      return (
        <div>
          <span>Accounts and Product Sections</span>
          <div>{sections?.length || 0} sections found</div>
        </div>
      );
    },
);

const state = store.getState();

export const getState = ({ userData = {}, userProfile = {}, config = {} } = {}): Record<
  string,
  any
> => ({
  session: {
    ...state.session,
    user: {
      ...state.session.user,
      id: 'JYYN1SC4iU0697',
      contact_name: 'Kamlesh J',
      logo_url: 'https://cdn.razorpay.com/logo_invert.svg',
      display_name: 'Kapil 12',
      user: {
        contact_mobile: '7798586889',
        email: 'kapil.thakur+150@razorpay.com',
        signup_via_email: 1,
      },
      userRole: 'owner',
      role: 'owner',
      isEmailSelfServeEnabled: true,
      findTag: () => false,
      isAllowedView: () => true,
      isOrgAllowedFunctionality: jest.fn(),
      contact_mobile: undefined,
      isWhatsappNotificationEnabled: jest.fn(),
      activation_status: undefined,
      isOrgAxis: undefined,
      isOrgRZP: undefined,
      isInstrumentRequestHidden: undefined,
      isWebsiteComplianceFlowEnabled: undefined,
      isFeatureEnabled: jest.fn(),
      isAllowedMultiple: () => true,
      ...userData,
    },
  },
  profile: {
    check_password: {
      data: {
        set_password: 1,
      },
    },
    bankAccount: {},
    ...userProfile,
  },
  config: {
    featureStatusConfig: {
      loading: false,
      data: {
        allow_cfb_international: true,
      },
    },
    ...config,
  },
});
