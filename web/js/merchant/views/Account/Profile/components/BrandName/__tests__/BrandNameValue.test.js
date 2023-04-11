import React from 'react';
import { render, screen, waitFor } from 'test-utils';
import BrandNameValue from 'merchant/views/Account/Profile/components/BrandName/BrandNameValue';

jest.mock('merchant/views/Account/Profile/components/UpdateBillingLabel', () => ({
  __esModule: true,
  default: () => <>Update Billing Label</>,
}));

const renderApp = ({ user } = {}) => {
  const renderOutput = render(<BrandNameValue />, {
    showModal: true,
    initialState: {
      session: {
        user: {
          billing_label: 'Billing label',
          id: 'K16F51VyNzg75l',
          ...user,
        },
      },
    },
  });
  return renderOutput;
};

describe('Brand Name Value', () => {
  describe('When billing label is defined', () => {
    test('should render Brand Name Value component', () => {
      renderApp();
      expect(screen.getByText('Billing label')).toBeInTheDocument();
    });

    test('should open Update Billing Label modal when Edit Billing Label button is clicked', async () => {
      renderApp();
      screen.getByRole('button', { name: 'Edit Billing Label' }).click();
      await waitFor(() => expect(screen.getByText('Update Billing Label')).toBeInTheDocument());
    });
  });

  describe('When billing label is undefined', () => {
    test('should render Set Billing Label', () => {
      renderApp({
        user: {
          billing_label: undefined,
        },
      });
      expect(screen.getByRole('button', { name: 'Set Billing Label' })).toBeInTheDocument();
    });

    test('should open Update Billing Label modal when Set Billing Label button is clicked', async () => {
      renderApp({
        user: {
          billing_label: undefined,
        },
      });
      screen.getByRole('button', { name: 'Set Billing Label' }).click();
      await waitFor(() => expect(screen.getByText('Update Billing Label')).toBeInTheDocument());
    });
  });
});
