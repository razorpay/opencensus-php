import React from 'react';
import MerchantAdditionalDetails from '../MerchantAdditionalDetails';
import { getModularConfig, updateModularConfig } from '../mocks/handlers';
import {
  fireEvent,
  render,
  screen,
  server,
  waitFor,
  waitForElementToBeRemoved,
} from 'apps/pos/src/services/test/test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    id: 'abc_123',
    step: 'additionalDetails',
    component: 'merchantAdditionalDetails',
  }),
}));

const renderApp = () => {
  render(<MerchantAdditionalDetails />);
};

describe('Test POS merchant additional details screen', () => {
  jest.setTimeout(30000);
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should not render additional details screen if modular config is failed to fetch', async () => {
    server.use(getModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/Something went wrong. Please try again./i)).toBeInTheDocument();
    });
  });

  test('should render additional details screen only if modular config is present', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('additional-details-spinner'));
    expect(screen.getByText('Miscellaneous Information')).toBeInTheDocument();

    expect(screen.getByRole('combobox', { name: /Annual Turnover/i })).toHaveValue(
      'More than ₹20L',
    );
    expect(screen.getByRole('combobox', { name: /Acquirer Preference/i })).toHaveValue('SBI');
    expect(screen.getByRole('textbox', { name: /Name of Store Manager\/ Cashier/i })).toHaveValue(
      'Anant Ambani',
    );
    expect(
      screen.getByRole('textbox', { name: /Mobile Number of Store Manager\/ Cashier/i }),
    ).toHaveValue('6234567890');
    expect(screen.getByRole('combobox', { name: /Marketing Plan/i })).toHaveValue(
      'Jana Bank (Non-SMP)',
    );
    expect(screen.getByRole('textbox', { name: /SI Partner/i })).toHaveValue('');
    expect(screen.getByRole('combobox', { name: /OMC/i })).toHaveValue('IOCL');
    expect(screen.getByRole('textbox', { name: /SAP Code/i })).toHaveValue('123456789');
    expect(screen.getByRole('radiogroup', { name: /Solution Type/i })).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'Integrated' })).toBeChecked();
  });

  test('should submit form successfully', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Miscellaneous Information')).toBeInTheDocument();
    });
    const submitButton = screen.getByRole('button', { name: /Continue to next step/i });
    await waitFor(() => {
      expect(submitButton).not.toBeDisabled();
      submitButton.click();
    });
    await waitFor(() => {
      expect(screen.getByText(/Successfully updated additional details/i)).toBeInTheDocument();
    });
  });

  test('should show error if submit api fails', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'failure' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Miscellaneous Information')).toBeInTheDocument();
    });
    const submitButton = screen.getByRole('button', { name: /Continue to next step/i });
    await waitFor(() => {
      expect(submitButton).not.toBeDisabled();
      submitButton.click();
    });
    await waitFor(() => {
      expect(screen.getByText('Something went wrong. Please try again.')).toBeInTheDocument();
    });
  });

  test('Submit button should be disabled until all mandatory fields are filled', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Miscellaneous Information')).toBeInTheDocument();
    });
    fireEvent.change(screen.getByRole('textbox', { name: /Name of Store Manager\/ Cashier/i }), {
      target: {
        value: '',
      },
    });
    const submitButton = screen.getByRole('button', { name: /Continue to next step/i });
    await waitFor(() => {
      expect(submitButton).toBeDisabled();
    });
  });
});
