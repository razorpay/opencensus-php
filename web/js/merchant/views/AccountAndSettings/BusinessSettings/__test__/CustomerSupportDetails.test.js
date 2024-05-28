import '@testing-library/jest-dom/extend-expect';
import React from 'react';

import * as showWhen from 'merchant/components/ShowWhen';
import store from 'merchant/store';
import CustomerSupportDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/CustomerSupportDetails';
import * as modals from 'merchant_common/reducers/modals';
import { render, screen, server, userEvent, waitFor } from 'test-utils';

import { fetchSupportDetail } from './fixtures/handlers';

const globalStore = store.getState();

const getInitialState = () => ({
  ...globalStore,
  session: {
    ...globalStore.session,
    user: {
      ...globalStore.session.user,
      userRole: 'owner',
      role: 'owner',
      isAdminOrOwner: true,
    },
  },
});

describe('Customer support details', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');

  const renderApp = ({ props = {}, initialState }) =>
    render(<CustomerSupportDetails {...props} />, {
      initialState,
    });

  beforeEach(() => {
    window.rzp_user = {};
    server.use(fetchSupportDetail());
    modalsSpy.mockClear();
  });

  test('should render support details title', async () => {
    renderApp({
      initialState: getInitialState(),
    });
    await waitFor(() => {
      expect(screen.getByText('Customer Support Details')).toBeInTheDocument();
    });
  });

  test.each([
    {
      name: 'Phone Number',
      value: '+91 9677 868778', // Formatted INDIAN phone number
      order: 0,
      type: 'Edit',
      phoneNumber: '9677868778', // INDIAN phone number
    },
    {
      name: 'Phone Number',
      value: '+60 13 27587 92', // Formatted MALAYSIAN phone number
      order: 0,
      type: 'Edit',
      phoneNumber: '+60132758792', // MALAYSIAN phone number
    },
    {
      name: 'Phone Number',
      value: '+65 9012 6257', // Formatted SINGAPORE phone number
      order: 0,
      type: 'Edit',
      phoneNumber: '+6590126257', // SINGAPORE phone number
    },
    {
      name: 'Phone Number',
      value: '+1 949-666-0936', // Formatted US phone number
      order: 0,
      type: 'Edit',
      phoneNumber: '+19496660936', // US phone number
    },
    { name: 'Email', value: 'support@example.com', order: 1, type: 'Edit' },
    { name: 'Website/ Contact Us Link', value: '----', order: 0, type: 'Add' },
  ])(
    'should render support details and call handleedit on click of edit icon %s',
    async ({ name, value, order, type, phoneNumber }) => {
      if (phoneNumber) {
        server.use(fetchSupportDetail(phoneNumber));
      }
      jest.spyOn(showWhen, 'showWhenUtil').mockImplementation(() => true);
      renderApp({
        initialState: getInitialState(),
      });
      await waitFor(() => {
        expect(screen.getByText(name)).toBeInTheDocument();
      });
      expect(screen.getByText(value)).toBeInTheDocument();

      const editButton = screen.getAllByRole('button', { name: type });
      await userEvent.click(editButton[order]);
      expect(modalsSpy).toHaveBeenCalledTimes(1);
    },
  );
});
