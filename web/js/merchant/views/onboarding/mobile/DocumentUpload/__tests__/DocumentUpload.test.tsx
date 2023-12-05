import React from 'react';
import {
  ADDRESS_PROOF_TYPES,
  BUSINESS_PROOF_TYPE_DOCS,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import DocumentUpload from '..';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';
import { render, screen, fireEvent, waitFor } from 'test-utils';

window.HTMLElement.prototype.scrollIntoView = () => {};

afterEach(() => {
  ActivationDB.reset();
});

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <DocumentUpload />;
};

describe('<DocumentUpload />', () => {
  test('should render all option available for Business Registration Proof', async () => {
    ActivationDB.update({
      business_type: '1',
      documents: {
        msme_certificate: [
          {
            id: 'It4NCPW4WHW8Nk',
            file_store_id: 'It4NFVRHbAueUB',
            merchant_id: 'IgSqDJUNuBAiOU',
            created_at: 1644226070,
          },
        ],
      },
    });
    render(<App />, {});
    await waitFor(() => {
      const businessRegistrationProofSelect = screen.getAllByPlaceholderText(
        'SELECT REGISTRATION PROOF TYPE',
      )[0];
      fireEvent.click(businessRegistrationProofSelect);
    });

    Object.values(BUSINESS_PROOF_TYPE_DOCS).forEach((value) => {
      expect(screen.getByText(value)).toBeInTheDocument();
    });
  });

  test('should not render msme option for Business Registration Proof if experiment is disabled', async () => {
    ActivationDB.update({
      business_type: '1',
      documents: {
        msme_certificate: [
          {
            id: 'It4NCPW4WHW8Nk',
            file_store_id: 'It4NFVRHbAueUB',
            merchant_id: 'IgSqDJUNuBAiOU',
            created_at: 1644226070,
          },
        ],
      },
    });
    render(<App />, {
      context: {
        mode: 'test',
        org: { id: '123' },
        user: { contact_name: 'susheela' },
        experiments: { isMsmeCertificateEnabled: false },
      },
    });
    await waitFor(() => {
      const businessRegistrationProofSelect = screen.getAllByPlaceholderText(
        'SELECT REGISTRATION PROOF TYPE',
      )[0];
      fireEvent.click(businessRegistrationProofSelect);
    });

    expect(screen.queryAllByText('Udyam certificate')).toHaveLength(0);
  });

  test('should render msme option for Business Registration Proof if experiment is enabled', async () => {
    ActivationDB.update({
      business_type: '1',
      documents: {
        msme_certificate: [
          {
            id: 'It4NCPW4WHW8Nk',
            file_store_id: 'It4NFVRHbAueUB',
            merchant_id: 'IgSqDJUNuBAiOU',
            created_at: 1644226070,
          },
        ],
      },
    });
    render(<App />, {
      context: {
        mode: 'test',
        org: { id: '123' },
        user: { contact_name: 'susheela' },
        experiments: { isMsmeCertificateEnabled: true },
      },
    });
    await waitFor(() => {
      const businessRegistrationProofSelect = screen.getAllByPlaceholderText(
        'SELECT REGISTRATION PROOF TYPE',
      )[0];
      fireEvent.click(businessRegistrationProofSelect);
    });

    expect(screen.queryAllByText('Udyam certificate')).toHaveLength(1);
  });

  test('should render all option available for Address ', async () => {
    ActivationDB.update({
      business_type: '4',
    });
    render(<App />, {});
    await waitFor(() => {
      const proofTypeSlect = screen.getAllByPlaceholderText('SELECT PROOF TYPE')[0];
      fireEvent.click(proofTypeSlect); //click on select
    });

    Object.keys(ADDRESS_PROOF_TYPES).forEach((key) => {
      expect(screen.getByText(ADDRESS_PROOF_TYPES[key].label)).toBeInTheDocument();
    });
  });

  test('should manage the uploaded state of all types individually', async () => {
    ActivationDB.update({
      business_type: '4',
    });
    render(<App />, {});
    const file = new File(['(⌐□_□)'], 'abc.png', { type: 'image/png' });
    const backUploadInput = screen.getAllByTestId('upload-input')[1];
    fireEvent.change(backUploadInput, { target: { files: [file] } }); //upload Back of the Aadhaar
    expect(screen.getByText('abc.png')).toBeInTheDocument();
    await waitFor(() => expect(screen.getByText('abc.png')).toBeInTheDocument());
    fireEvent.click(screen.getAllByPlaceholderText('SELECT PROOF TYPE')[0]); //click on select
    fireEvent.click(screen.getByText('Voter Id')); //select Voter ID as preferred type
    /*     expect not uploaded state for Voter Id       */
    await waitFor(() => {
      expect(screen.queryByText('File Uploaded')).not.toBeInTheDocument();
    });
  });

  test.skip('on changing category or sub-category expected doc is visible or not', async () => {
    render(<App />, {});
    expect(screen.queryByText('Business Registration Proof')).not.toBeInTheDocument();
    expect(screen.queryByText('Shop Establishment Number')).not.toBeInTheDocument();
    ActivationDB.update({
      business_type: '1',
      shop_establishment_verifiable_zone: true,
    });
    await waitFor(() =>
      expect(screen.queryByText('Business Registration Proof')).toBeInTheDocument(),
    );
    await waitFor(() =>
      fireEvent.change(screen.getByLabelText('Shop Establishment Number'), {
        target: { value: 'NDJF3243dsfs23' },
      }),
    );
    waitFor(() => fireEvent.blur(screen.getByLabelText('Shop Establishment Number')));
  });

  test('should show aadhaar error message', async () => {
    ActivationDB.update({
      business_type: '1',
      activation_form_milestone: 'L1',
      verification_error_codes: {
        poa_verification_status: 'AADHAAR_BACK_NOT_MATCHED',
      },
    });
    render(<App />, {});
    await waitFor(() => {
      expect(screen.queryByText(/Input document does not match Aadhaar back/i)).toBeInTheDocument();
    });
  });

  test('should not show aadhaar ekyc for Trust Business Type', async () => {
    ActivationDB.update({
      business_type: '9',
      activation_form_milestone: 'L1',
    });
    render(<App />, {});
    await waitFor(() => {
      expect(screen.queryByText('Aadhaar Verification ( via OTP )')).toBeNull();
    });
  });
});
