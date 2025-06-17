import React from 'react';

import ConfirmationBanner from 'apps/pos/src/app/views/SelfServe/OrderDetails/ConfirmationBanner';
import { render, screen, waitFor } from 'test-utils';
import * as posCustomHooks from 'apps/pos/src/app/views/SelfServe/hooks';

const mockedUsedNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
  useParams: () => ({
    orderId: 'KwxwAgItmmXdp',
  }),
}));

describe('Confirmation Banner Component', () => {
  test('should render without any error', async () => {
    render(<ConfirmationBanner arrivingDate={1655193661} />);
    await waitFor(() => {
      expect(screen.getByText('Your order is successfully placed!')).toBeInTheDocument();
    });
  });

  test('should render in mobile view without any error', async () => {
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    render(<ConfirmationBanner arrivingDate={1655193661} />);
    await waitFor(() => {
      expect(screen.getByText('Your order is successfully placed!')).toBeInTheDocument();
    });
  });
});
