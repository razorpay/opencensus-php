import '@testing-library/jest-dom/extend-expect';
import * as context from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import store from 'merchant/store';
import AccountDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2';
import * as modals from 'merchant_common/reducers/modals';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

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
    { name: 'Display Name', value: 'Kapil 12', isHandler: true, isEditable: true, order: 0 },
    {
      name: 'Email',
      value: 'kapil.thakur+150@razorpay.com',
      isHandler: false,
      type: 'update',
      isEditable: true,
      order: 2,
    },
    { name: 'Name', value: 'Kapil Thakur', isHandler: false, isEditable: true, order: 1 },
    { name: 'Phone Number', value: '7798586889', isHandler: true, isEditable: true, order: 3 },
    {
      name: 'Email',
      value: 'kapil.thakur+150@razorpay.com',
      isHandler: false,
      type: 'add',
      isEditable: true,
      order: 2,
    },
  ])(
    'should render user details and call handleedit on click of edit icon %s',
    async ({ name, value, isEditable, order }) => {
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
      expect(screen.getByText(value)).toBeInTheDocument();

      if (isEditable) {
        const editButton = screen.getAllByRole('button');
        await userEvent.click(editButton[order]);
        expect(modalsSpy).toHaveBeenCalledTimes(1);
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
