import React from 'react';

import { useActivationState } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ActivationModal/activationStates';
import { render, screen, userEvent, waitFor } from 'test-utils';

import ActivationModal from '../index';
import { ACTION_LIST } from '../constants';
import { server, queryClient, purposeCodeHandlers } from './mocks/handlers';
import { resetActivationState } from './mocks/states';

const onDismiss = jest.fn();

const renderModal = (props = {}) => {
  render(<ActivationModal isOpen={true} onDismiss={onDismiss} {...props} />);
};

describe('Test ActivationModal', () => {
  beforeAll(() => server.listen());
  beforeEach(() => {
    server.use(purposeCodeHandlers.success());
  });
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
    resetActivationState();
  });
  afterAll(() => server.close());

  it('should render without breaking', () => {
    renderModal();

    expect(screen.getByText('Activate international bank transfers')).toBeInTheDocument();

    ACTION_LIST.filter(({ label }) => label !== 'International Details').forEach(({ label }) => {
      expect(screen.getByText(label)).toBeInTheDocument();
    });
  });

  it('should show purpose code form', async () => {
    renderModal();

    await waitFor(() => {
      expect(
        screen.getByText(/Helps us determine the purpose of your business/),
      ).toBeInTheDocument();
    });
  });

  it('should not proceed if purpose code is not selected', async () => {
    renderModal();

    await waitFor(() => {
      expect(
        screen.getByText(/Helps us determine the purpose of your business/),
      ).toBeInTheDocument();
    });

    const continueButton = screen.getByTestId('continue-button');

    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.queryByText('Importer - Exporter code')).not.toBeInTheDocument();
    });
  });

  it('should show IEC Code step', async () => {
    server.use(purposeCodeHandlers.success(), purposeCodeHandlers.patchPurposeCode());

    renderModal();

    await waitFor(() => {
      expect(screen.getByText(/P0101/)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText(/P0101/));

    const continueButton = screen.getByTestId('continue-button');

    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText('Importer - Exporter code')).toBeInTheDocument();
    });

    // click on Yes option for IEC Code
    const yesOption = screen.getByLabelText('Yes');
    await userEvent.click(yesOption);

    await waitFor(() => screen.getByPlaceholderText('A1234567890'));

    // enter IEC Code
    const iecCodeInput = screen.getByPlaceholderText('A1234567890');
    await userEvent.type(iecCodeInput, 'IEC123456');

    // click on accept tnc
    await userEvent.click(screen.getByText(/I accept the/));

    await userEvent.click(continueButton);

    expect(continueButton).toBeDisabled();

    await waitFor(() => {
      expect(screen.getByText(/Confirmation of authorised signatory/)).toBeInTheDocument();
    });
  });

  it('should not allow to proceed if iec code not provided', async () => {
    renderModal();

    await waitFor(() => {
      expect(screen.getByText(/P0101/)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText(/P0101/));

    const continueButton = screen.getByTestId('continue-button');

    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText(/Importer - Exporter code/)).toBeInTheDocument();
    });

    // click on Yes option for IEC Code
    const yesOption = screen.getByLabelText('Yes');
    await userEvent.click(yesOption);

    await waitFor(() => screen.getByPlaceholderText('A1234567890'));

    await userEvent.click(continueButton);

    expect(screen.getByText(/Please enter a valid IEC code/)).toBeInTheDocument();
  });

  it('should show Video KYC step', async () => {
    server.use(
      purposeCodeHandlers.success(),
      purposeCodeHandlers.patchPurposeCode(),
      purposeCodeHandlers.postGenerateLink(),
    );

    renderModal();

    await waitFor(() => {
      expect(screen.getByText(/P0101/)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText(/P0101/));

    const continueButton = screen.getByTestId('continue-button');

    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText(/Importer - Exporter code/)).toBeInTheDocument();
    });

    // click on Yes option for IEC Code
    const yesOption = screen.getByLabelText('Yes');
    await userEvent.click(yesOption);

    await waitFor(() => screen.getByPlaceholderText('A1234567890'));

    // enter IEC Code
    const iecCodeInput = screen.getByPlaceholderText('A1234567890');
    await userEvent.type(iecCodeInput, 'IEC123456');

    // click on accept tnc
    await userEvent.click(screen.getByText(/I accept the/));

    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText(/Confirmation of authorised signatory/)).toBeInTheDocument();
    });

    // click on Yes option for Video KYC
    await userEvent.click(screen.getByLabelText('Yes, I am'));

    await waitFor(() => {
      expect(screen.getAllByText('Start Video KYC')).toHaveLength(2);
    });
  });

  it('should show international details step', async () => {
    renderModal({
      purposeCode: 'P0101',
      iecCode: 'IEC123456',
      step: 3,
    });

    // assert international details screen
    await waitFor(() => {
      expect(
        screen.getByText(/saved these important business details for you/),
      ).toBeInTheDocument();
    });

    // assert purpose code and iec code
    expect(screen.getByText('P0101')).toBeInTheDocument();
    expect(screen.getByText('IEC123456')).toBeInTheDocument();

    // click on accept tnc
    const acceptTnc = screen.getByLabelText(/I accept the/);
    expect(acceptTnc).toBeInTheDocument();
    await userEvent.click(acceptTnc);

    // click on continue button
    const continueButton = screen.getByTestId('continue-button');
    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText(/Confirmation of authorised signatory/)).toBeInTheDocument();
    });
  });

  it('should not show IEC code on international details step if iec code is NOT_APPLICABLE', async () => {
    renderModal({
      purposeCode: 'P0101',
      iecCode: 'NOT_APPLICABLE',
      step: 3,
    });

    // assert international details screen
    await waitFor(() => {
      expect(
        screen.getByText(/saved these important business details for you/),
      ).toBeInTheDocument();
    });

    // assert purpose code and iec code
    expect(screen.getByText('P0101')).toBeInTheDocument();
    expect(screen.queryByText(/Importer Exporter code/)).not.toBeInTheDocument();

    // click on accept tnc
    const acceptTnc = screen.getByLabelText(/I accept the/);
    expect(acceptTnc).toBeInTheDocument();
    await userEvent.click(acceptTnc);

    // click on continue button
    const continueButton = screen.getByTestId('continue-button');
    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText(/Confirmation of authorised signatory/)).toBeInTheDocument();
    });
  });

  it('should not show accept tnc checkbox on international details step if edd is verified', async () => {
    renderModal({
      purposeCode: 'P0101',
      iecCode: 'IEC123456',
      step: 3,
      isEddVerified: true,
    });

    // assert international details screen
    await waitFor(() => {
      expect(
        screen.getByText(/saved these important business details for you/),
      ).toBeInTheDocument();
    });

    // assert purpose code and iec code
    expect(screen.getByText('P0101')).toBeInTheDocument();
    expect(screen.getByText('IEC123456')).toBeInTheDocument();

    // assert accept tnc checkbox
    expect(screen.queryByLabelText(/I accept the/)).not.toBeInTheDocument();

    // click on continue button
    const continueButton = screen.getByTestId('continue-button');
    await userEvent.click(continueButton);

    await waitFor(() => {
      expect(screen.getByText(/Confirmation of authorised signatory/)).toBeInTheDocument();
    });
  });

  it('should not show video kyc step if edd is verified', async () => {
    server.use(purposeCodeHandlers.success(), purposeCodeHandlers.patchPurposeCode());

    renderModal({
      isEddVerified: true,
    });

    expect(screen.queryByText(/Video KYC/)).not.toBeInTheDocument();
  });
});
