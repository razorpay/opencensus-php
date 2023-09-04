import React from 'react';
import { render, screen, waitFor, userEvent, server } from 'test-utils';

import DynamicFieldsPl from 'merchant/views/Settings/Configuration/dynamicFieldsPl';

import {
  fetchDynamicFieldsSuccess,
  updateDynamicFields,
} from 'merchant/views/PaymentLinks/__test__/mocks/handlers';

function renderApp(props = {}, initialState = {}) {
  return render(<DynamicFieldsPl {...props} />, { initialState });
}

describe('DynamicFieldsPl', () => {
  test('should render required configuration input fields', () => {
    renderApp();

    expect(screen.getByPlaceholderText('Enter field label')).toBeInTheDocument();
    expect(screen.getByRole('checkbox', { name: 'Is Mandatory' })).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Enter the number')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Select field type')).toBeInTheDocument();
  });

  test('should be able to prefill configuration', async () => {
    server.use(fetchDynamicFieldsSuccess());

    renderApp();

    // Get initial configs.
    await waitFor(() => expect(screen.queryByLabelText('configs-loading')).not.toBeInTheDocument());

    expect((screen.getByPlaceholderText('Enter field label') as HTMLInputElement).value).toBe(
      'Account Number',
    );
    expect(screen.getByRole('checkbox', { name: 'Is Mandatory' })).toBeChecked();
    expect((screen.getByPlaceholderText('Enter the number') as HTMLInputElement).value).toBe('5');
    expect((screen.getByPlaceholderText('Select field type') as HTMLInputElement).value).toBe(
      'String',
    );
  });

  test('should be able to save config changes', async () => {
    server.use(fetchDynamicFieldsSuccess(null), updateDynamicFields());

    renderApp();

    // Get initial configs.
    await waitFor(() => expect(screen.queryByLabelText('configs-loading')).not.toBeInTheDocument());

    // Enter field label.
    await userEvent.type(screen.getByPlaceholderText('Enter field label'), 'Test Field');
    // Make the field mandatory.
    await userEvent.click(screen.getByRole('checkbox', { name: 'Is Mandatory' }));
    // Enter masking character number.
    await userEvent.type(screen.getByPlaceholderText('Enter the number'), '10');
    // Select type from dropdown options.
    await userEvent.click(screen.getByPlaceholderText('Select field type'));
    await userEvent.click(screen.getByText('Integer'));

    const saveCTA = screen.getByTestId('save-config-cta');

    expect(saveCTA).not.toBeDisabled();

    // Save changes.
    await userEvent.click(saveCTA);

    await waitFor(() => expect(saveCTA).not.toBeDisabled());

    expect(screen.getByText('Configurations updated successfully')).toBeInTheDocument();
  });
});
