import { render, screen } from '@testing-library/react';
import Mealcard from 'merchant/views/Settings/PaymentMethods/components/paymentMethodIcons/mealcard';
import Sodexo from 'assets/payment-methods/sodexo.png';

test('should render MealCard component with sodexo image', () => {
  render(<Mealcard />);

  const sodexoImg = screen.getByAltText('Sodexo');

  expect(sodexoImg).toBeInTheDocument();
  expect(sodexoImg).toHaveAttribute('src', Sodexo);
  expect(sodexoImg).toHaveAttribute('alt', 'Sodexo');
  expect(sodexoImg).toHaveAttribute('width', '30px');
  expect(sodexoImg).toHaveAttribute('height', '30px');
});
