import '@testing-library/jest-dom/extend-expect';
import * as showWhen from 'merchant/components/ShowWhen';
import store from 'merchant/store';
import CustomerSupportDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/CustomerSupportDetails';
import * as modals from 'merchant_common/reducers/modals';
import React from 'react';
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
    { name: 'Phone Number', value: '+91-9677868778', order: 0, type: 'Edit' },
    { name: 'Email', value: 'support@example.com', order: 1, type: 'Edit' },
    { name: 'Website/ Contact Us Link', value: '----', order: 0, type: 'Add' },
  ])(
    'should render support details and call handleedit on click of edit icon %s',
    async ({ name, value, order, type }) => {
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
