import React from 'react';

import OrderDetailsCTA from 'apps/pos/src/app/views/SelfServe/OrderDetails/OrderDetailsCTA';
import { render, screen, userEvent, waitFor } from 'test-utils';

const mockedUsedNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
  useParams: () => ({
    orderId: 'KwxwAgItmmXdp',
  }),
}));

describe('Order Details CTA Component', () => {
  test('navigate to catalog page on click of Continue Shopping', async () => {
    render(<OrderDetailsCTA />);
    const continueBtn = screen.getByRole('button', {
      name: 'Continue Shopping',
    });
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(mockedUsedNavigate).toHaveBeenCalledWith('/pos/catalog');
    });
  });
  test('navigate to orders page on click of View Orders', async () => {
    render(<OrderDetailsCTA />);
    const ordersBtn = screen.getByRole('button', {
      name: 'View Orders',
    });
    expect(ordersBtn).toBeInTheDocument();
    await userEvent.click(ordersBtn);
    await waitFor(() => {
      expect(mockedUsedNavigate).toHaveBeenCalledWith('/pos/orders');
    });
  });
});
