import React from 'react';

import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { STEPS } from '../constants';
import IecCode from '../index';

describe('IecCode', () => {
  it('should render without breaking', () => {
    render(<IecCode />);

    expect(screen.getByText('Importer - Exporter code')).toBeInTheDocument();
    expect(screen.getByText('Do you have an Import Export Code (IEC)?')).toBeInTheDocument();

    const options = screen.getAllByRole('radio');

    expect(options).toHaveLength(2);
  });

  it('should show Importer Exporter Code input field for yes option', async () => {
    render(<IecCode />);

    const yesOption = screen.getByLabelText('Yes');

    expect(yesOption).toBeInTheDocument();
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
    await userEvent.click(yesOption);

    const fields = useActivationState.getState().steps?.[STEPS.IEC_CODE]?.fields;
    expect(fields.iecCodeOption).toBe('yes');

    await waitFor(() => screen.getByPlaceholderText('A1234567890'));
    expect(screen.getByPlaceholderText('A1234567890')).toBeInTheDocument();
    expect(screen.getByLabelText(/I accept the/)).toBeInTheDocument();
  });

  it('should show not_applicable consent checkbox for no option', async () => {
    render(<IecCode />);

    const noOption = screen.getByLabelText(/No, because it is not applicable for me/);

    expect(noOption).toBeInTheDocument();
    expect(screen.queryByTestId('acceptNotApplicableTnc')).not.toBeInTheDocument();
    await userEvent.click(noOption);

    let fields = useActivationState.getState().steps?.[STEPS.IEC_CODE]?.fields;
    expect(fields.iecCodeOption).toBe('NOT_APPLICABLE');
    expect(fields.iecCode).toBe('NOT_APPLICABLE');

    await waitFor(() => screen.getByTestId('acceptNotApplicableTnc'));

    const acceptNotApplicableTnc = screen.getByLabelText(/I hereby declare/);
    await userEvent.click(acceptNotApplicableTnc);

    fields = useActivationState.getState().steps?.[STEPS.IEC_CODE]?.fields;
    expect(fields.acceptNotApplicableTnc).toBe('yes');

    await waitFor(() => expect(screen.getByLabelText(/I hereby declare/)).toBeChecked());

    expect(screen.getByLabelText(/I accept the/)).toBeInTheDocument();
  });
});
