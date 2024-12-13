import { fireEvent } from '@testing-library/react';

import { GetStarted } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/GetStarted';
import { GetStartedCards } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { lazyRenderComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers';

const mockCreateRule = jest.fn();

const props = {
  onCreateRule: mockCreateRule,
};

const renderWithProps = lazyRenderComponent(GetStarted);

describe('GetStarted', () => {
  it('renders the "Create New" heading', () => {
    const { getByText } = renderWithProps(props);
    expect(getByText('Create New')).toBeInTheDocument();
  });

  it('renders two CardButton components', () => {
    const { getByText } = renderWithProps(props);
    expect(getByText(GetStartedCards.shipping.title)).toBeInTheDocument();
    expect(getByText(GetStartedCards.payment.title)).toBeInTheDocument();
  });

  it('calls onCreateShippingRule when the shipping rule button is clicked', () => {
    const { getByRole } = renderWithProps(props);
    fireEvent.click(getByRole('button', { name: GetStartedCards.shipping.accessibilityLabel }));
    expect(mockCreateRule).toHaveBeenCalledWith('shipping');
  });

  it('calls onCreatePaymentRule when the payment rule button is clicked', () => {
    const { getByRole } = renderWithProps(props);
    fireEvent.click(getByRole('button', { name: GetStartedCards.payment.accessibilityLabel }));
    expect(mockCreateRule).toHaveBeenCalledWith('payment');
  });
});
