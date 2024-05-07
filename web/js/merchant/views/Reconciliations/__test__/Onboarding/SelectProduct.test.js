import SelectProduct from 'merchant/views/Reconciliations/Onboarding/SelectProduct';
import { render, screen } from 'test-utils';

import { PRODUCT, MERCHANT_META } from './../constants';

const renderSelectProduct = (props = {}) => {
  return render(<SelectProduct {...props} />);
};

describe('Tests for Onboarding component onboardingView - Recon Saas', () => {
  test('Should render recon product selection without errors', () => {
    expect(() => renderSelectProduct({ merchantMeta: MERCHANT_META })).not.toThrowError();
  });

  test('Should render recon product selection text', () => {
    renderSelectProduct({ merchantMeta: MERCHANT_META });
    expect(screen.getByText(PRODUCT.header)).toBeInTheDocument();
    expect(screen.getByText(PRODUCT.description)).toBeInTheDocument();
  });

  test('Should render back button', () => {
    const { getByText } = renderSelectProduct({ merchantMeta: MERCHANT_META });
    expect(getByText('Back')).toBeInTheDocument();
  });
});
