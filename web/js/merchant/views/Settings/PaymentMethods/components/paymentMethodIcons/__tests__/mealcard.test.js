import { render, screen } from '@testing-library/react';
import Mealcard from 'merchant/views/Settings/PaymentMethods/components/paymentMethodIcons/mealcard';
import Pluxee from 'assets/payment-methods/pluxee.png';

test('should render MealCard component with pluxee image', () => {
  render(<Mealcard />);

  const pluxeeImg = screen.getByAltText('Pluxee');

  expect(pluxeeImg).toBeInTheDocument();
  expect(pluxeeImg).toHaveAttribute('src', Pluxee);
  expect(pluxeeImg).toHaveAttribute('alt', 'Pluxee');
  expect(pluxeeImg).toHaveAttribute('width', '30px');
  expect(pluxeeImg).toHaveAttribute('height', '30px');
});
