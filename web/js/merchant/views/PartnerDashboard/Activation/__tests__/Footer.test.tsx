import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import Footer from 'merchant/views/PartnerDashboard/Activation/Components/Footer';
import { FOOTER_BUTTONS } from 'merchant/views/PartnerDashboard/Activation/utils/ActivationUtils';

describe('Submit L1 Form Section', () => {
  const props = {
    footerButtons: [FOOTER_BUTTONS.SUBMIT_L1_FORM],
    canSubmitL1Form: true,
    isConentTNC: false,
  };

  test('Show show the correct footer for submit l1 form ', () => {
    render(<Footer {...props} />);
    expect(screen.getByText('Submit and Verify')).toBeInTheDocument();
  });

  test('Submit and verify button should be disabled if consent is not given', () => {
    render(<Footer {...props} />);
    expect(screen.getByRole('button', { name: 'Submit and Verify' })).toBeDisabled();
  });

  test('Submit and verify button should be enabled if consent is given', () => {
    const submitProps = {
      footerButtons: [FOOTER_BUTTONS.SUBMIT_L1_FORM],
      isConsentTNC: true,
    };
    render(<Footer {...submitProps} />);
    expect(screen.getByRole('button', { name: 'Submit and Verify' })).toBeEnabled();
  });
});
