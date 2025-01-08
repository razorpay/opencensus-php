import React from 'react';

import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { setPurposeCodeAndIecCode } from './mocks/states';
import { STEPS } from '../constants';
import InternationalDetails from '../index';

describe('InternationalDetails', () => {
  it('should render without breaking', () => {
    render(<InternationalDetails />);

    expect(screen.getByText('International details')).toBeInTheDocument();

    const acceptTnc = screen.getByLabelText(/I accept the/);
    expect(acceptTnc).toBeInTheDocument();
  });

  it('should show acceptTnc checkbox', async () => {
    render(<InternationalDetails />);

    const acceptTnc = screen.getByLabelText(/I accept the/);

    expect(acceptTnc).toBeInTheDocument();
    expect(screen.queryByTestId('acceptTnc')).not.toBeInTheDocument();
    await userEvent.click(acceptTnc);

    const fields = useActivationState.getState().steps?.[STEPS.INTERNATIONAL_DETAILS]?.fields;
    expect(fields.acceptTnc).toBe('yes');

    await waitFor(() => expect(screen.getByLabelText(/I accept the/)).toBeChecked());
  });

  it('should show purposeCode and iecCode', async () => {
    setPurposeCodeAndIecCode();

    render(<InternationalDetails />);

    const fields = useActivationState.getState().steps?.[STEPS.INTERNATIONAL_DETAILS]?.fields;
    expect(fields.purposeCode).toBe('TEST_PURPOSE_CODE');
    expect(fields.iecCode).toBe('TEST_IEC_CODE');

    await waitFor(() => screen.getByText('TEST_PURPOSE_CODE'));

    expect(screen.getByText('TEST_PURPOSE_CODE')).toBeInTheDocument();
    expect(screen.getByText('TEST_IEC_CODE')).toBeInTheDocument();
  });
});
