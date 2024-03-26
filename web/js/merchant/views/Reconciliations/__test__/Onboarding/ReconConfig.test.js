import ReconConfig from 'merchant/views/Reconciliations/Onboarding/ReconConfig';
import { render, screen } from 'test-utils';

import { PRODUCT } from './../constants';

const renderConfig = (props = {}) => {
  render(<ReconConfig {...props} />);
};

describe('Tests for Onboarding component onboardingView - Recon Saas', () => {
  test('Should render recon config selection without errors', () => {
    expect(renderConfig).not.toThrowError();
  });
  test('Should ask user to select a process', () => {
    renderConfig({
      product: PRODUCT,
      isConfigCreation: true,
      handleCtaClick: jest.fn(),
    });
    expect(screen.getByText(/Select a recon process/i)).toBeInTheDocument();
  });
  test('Should load the recon config selection screen', () => {
    renderConfig({
      product: PRODUCT,
      isConfigCreation: true,
      handleCtaClick: jest.fn(),
    });
    const reconType = PRODUCT.reconTypes[Object.keys(PRODUCT.reconTypes)[0]]?.header;
    expect(screen.getByText(reconType)).toBeInTheDocument();
  });
});
