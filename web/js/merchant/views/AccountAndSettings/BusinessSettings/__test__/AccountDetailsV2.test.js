import '@testing-library/jest-dom/extend-expect';
import React from 'react';

import * as context from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';
import store from 'merchant/store';
import AccountDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2';
import * as modals from 'merchant_common/reducers/modals';
import { render, screen, userEvent, waitFor } from 'test-utils';

const globalStore = store.getState();

const getInitialState = (userData) => ({
  ...globalStore,
  session: {
    ...globalStore.session,
    user: {
      ...globalStore.session.user,
      id: 'JYYN1SC4iU0697',
      contact_name: 'Kamlesh J',
      contact_email: 'owner+12@rzp.com',
      contact_mobile: '9999829384',
      logo_url: 'https://cdn.razorpay.com/logo_invert.svg',
      display_name: 'Kapil 12',
      user: {
        contact_mobile: '7798586889',
        email: 'kapil.thakur+150@razorpay.com',
        signup_via_email: 1,
        name: 'Kapil Thakur',
        merchants: {},
      },
      userRole: 'owner',
      role: 'owner',
      isEmailSelfServeEnabled: true,
      isAdminOrOwner: true,
      isContactMobileChangeAllowed: true,
      isContactDetailsRevamp: false,
      isUserNameUpdateEnabled: true,
      findTag: () => false,
      ...userData,
    },
  },
});

const defaultProps = {
  isMobile: false,
};

describe('Merchant Profile Section Version 2', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');

  const renderApp = ({ props = {}, initialState }) =>
    render(<AccountDetails {...defaultProps} {...props} />, {
      initialState,
      showModal: true,
    });

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test('should render account details title', () => {
    const initialState = getInitialState();
    renderApp({
      initialState,
    });
    expect(screen.getByText('Your Account Details')).toBeInTheDocument();
  });

  test.each([
    {
      id: 'display_name',
      name: 'Display Name',
      value: 'Kapil 12',
      isHandler: true,
      isEditable: true,
      order: 0,
    },
    {
      id: 'email',
      name: 'Email',
      value: 'kapil.thakur+150@razorpay.com',
      isHandler: false,
      type: 'update',
      isEditable: true,
      order: 2,
    },
    {
      id: 'name',
      name: 'Name',
      value: 'Kapil Thakur',
      isHandler: false,
      isEditable: true,
      order: 1,
    },
    {
      id: 'phone_number',
      name: 'Phone Number',
      value: '7798586889',
      isHandler: true,
      isEditable: true,
      order: 3,
    },
  ])(
    'should render user details and call handleedit on click of edit icon %s',
    async ({ id, name, value, isEditable, order }) => {
      jest.spyOn(context, 'useTwoFactorVerificationContext').mockImplementation(() => {
        return {
          criticalFlow: ({ onUserTwoFaVerified, onFlowTermination }) => {
            onUserTwoFaVerified();
            onFlowTermination();
          },
        };
      });
      const initialState = getInitialState();
      renderApp({
        initialState,
      });
      expect(screen.getByText(name)).toBeInTheDocument();
      if (id === 'phone_number')
        expect(screen.getByText(getI18FormattedPhoneNumber(value))).toBeInTheDocument();
      else expect(screen.getByText(value)).toBeInTheDocument();
      if (isEditable) {
        const editButton = screen.getAllByRole('button');
        await userEvent.click(editButton[order]);

        if (id === 'display_name') {
          waitFor(() => {
            expect(screen.getByText('Edit new display name')).toBeInTheDocument();
          });
        } else if (id === 'name') {
          waitFor(() => {
            expect(screen.getByText('Edit new profile name')).toBeInTheDocument();
          });
        } else {
          waitFor(() => {
            expect(modalsSpy).toHaveBeenCalledTimes(1);
          });
        }
      }
    },
  );

  test.each([
    { name: 'Name', value: 'Kamlesh J' },
    { name: 'Email', value: 'owner+12@rzp.com' },
    { name: 'Phone Number', value: '9999829384' },
  ])('should render user details and call handleedit on click of edit icon %s', ({ value }) => {
    const initialState = getInitialState({
      userRole: 'admin',
    });
    renderApp({
      initialState,
    });
    expect(screen.getByText('Owner’s Account Details')).toBeInTheDocument();
    expect(screen.getByText(value)).toBeInTheDocument();
  });
});
