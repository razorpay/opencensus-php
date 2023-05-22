import store from 'merchant/store';
import React from 'react';

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Verification',
  () => () => {
    return (
      <div>
        <span>Verification Module</span>
      </div>
    );
  },
);

jest.mock('common/components/Collapsible', () => ({ children, open }) => {
  return (
    <div>
      <span>Collapsible Component</span>
      {open ? children : null}
    </div>
  );
});

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/UserInfo',
  () =>
    ({ onClick, infoData }) => {
      return (
        <div>
          {infoData.map((each, index) => {
            return (
              <div key={index}>
                <span>{each.displayName}</span>
                {each.isEditEnable && (
                  <button onClick={() => onClick(each)}>{each.displayName}</button>
                )}
              </div>
            );
          })}
        </div>
      );
    },
);

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/Verification',
  () => () => {
    return (
      <div>
        <span>Verification v2 Module</span>
      </div>
    );
  },
);

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/UserInfo',
  () =>
    ({ onClick, infoData }) => {
      return (
        <div>
          {infoData.map((each, index) => {
            return (
              <div key={index}>
                <span>{each.displayName}</span>
                {each.isEditEnable && (
                  <button onClick={() => onClick(each)}>{each.displayName}</button>
                )}
              </div>
            );
          })}
        </div>
      );
    },
);

const state = store.getState();

export const getState = ({ userData = {}, userProfile = {}, appConfig = {} } = {}): Record<
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
        name: 'Kamlesh J',
      },
      userRole: 'owner',
      role: 'owner',
      isEmailSelfServeEnabled: true,
      isAdminOrOwner: true,
      isContactMobileChangeAllowed: true,
      isContactDetailsRevamp: false,
      findTag: () => false,
      ...userData,
    },
  },
  profile: {
    check_password: {
      data: {
        set_password: 1,
      },
    },
    ...userProfile,
  },
  app: {
    isMobileResolution: false,
    ...appConfig,
  },
});
