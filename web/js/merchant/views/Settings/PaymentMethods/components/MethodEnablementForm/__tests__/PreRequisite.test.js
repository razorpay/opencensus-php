import PreRequisiteTab from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/components/PreRequisite';
import { render, screen, userEvent } from 'test-utils';

const renderComponent = (props = {}, initialState = {}) => {
  return render(<PreRequisiteTab {...props} />, { initialState });
};

const selfAttestmentTypes = ['Self-Attestation', 'Digital Signature of the Issuing Authority'];

describe('Tests for PreRequisiteTab component - MethodEnablementForm', () => {
  test('should render properly', () => {
    renderComponent();

    //tab header should be visible
    expect(screen.getByText('Pre-requisite Information')).toBeInTheDocument();
  });

  test('should show only one way by default', () => {
    renderComponent();

    expect(screen.getByText(selfAttestmentTypes[0])).toBeInTheDocument();
    expect(screen.queryByText(selfAttestmentTypes[1])).not.toBeVisible();
  });

  test('should show toggle for alternate ways', () => {
    renderComponent();

    expect(
      screen.getByRole('button', { name: 'Know alternate ways of document verification' }),
    ).toBeInTheDocument();
  });

  test('should hide/show alternate ways on toggle click', async () => {
    renderComponent();
    const toggleButton = screen.getByText('Know alternate ways of document verification');

    expect(toggleButton).toBeInTheDocument();
    expect(screen.queryByText(selfAttestmentTypes[1])).not.toBeVisible();

    await userEvent.click(toggleButton);

    await expect(screen.getByText(selfAttestmentTypes[1])).toBeVisible();
  });
});
