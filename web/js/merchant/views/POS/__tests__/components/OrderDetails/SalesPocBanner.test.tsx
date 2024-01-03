import React from 'react';

import SalesPocBanner from 'merchant/views/POS/OrderDetails/SalesPocBanner';
import { fireEvent, render, screen, server, userEvent, waitFor } from 'test-utils';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { updateSalePoc } from 'merchant/views/POS/__tests__/mocks/handlers';

const mockedUsedNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
  useParams: () => ({
    orderId: 'KwxwAgItmmXdp',
  }),
}));

const onSalesPocUpdateSpy = jest.fn();
let showNotificationSpy;

describe('Sales Poc Banner Component', () => {
  beforeAll(() => {
    showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  });
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });
  test('renders without error', () => {
    render(<SalesPocBanner orderId="KwxwAgItmmXdp" onSalesPocUpdate={onSalesPocUpdateSpy} />);
    expect(screen.getByText('Have you been assisted by our Sales Executive?')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Enter Code')).toBeInTheDocument();
  });

  test('handles form submission failure', async () => {
    server.use(updateSalePoc({ isSuccess: false }));
    render(<SalesPocBanner orderId="KwxwAgItmmXdp" onSalesPocUpdate={onSalesPocUpdateSpy} />);

    const textInput = screen.getByPlaceholderText('Enter Code') as HTMLInputElement;
    fireEvent.change(textInput, { target: { value: '12345' } });

    expect(screen.getByText('Submit')).toBeInTheDocument();
    expect(textInput.value).toBe('12345');

    await userEvent.click(screen.getByText('Submit'));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Something went wrong! Please try again.',
      });
    });
  });

  test('handles form submission failure on receiving data value null', async () => {
    server.use(updateSalePoc({ isSuccess: true, noData: true }));
    render(<SalesPocBanner orderId="KwxwAgItmmXdp" onSalesPocUpdate={onSalesPocUpdateSpy} />);

    const textInput = screen.getByPlaceholderText('Enter Code') as HTMLInputElement;
    fireEvent.change(textInput, { target: { value: '12345' } });

    expect(screen.getByText('Submit')).toBeInTheDocument();
    expect(textInput.value).toBe('12345');

    await userEvent.click(screen.getByText('Submit'));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Something went wrong! Please try again.',
      });
    });
  });

  test('handles form submission success', async () => {
    server.use(updateSalePoc({ isSuccess: true }));
    render(<SalesPocBanner orderId="KwxwAgItmmXdp" onSalesPocUpdate={onSalesPocUpdateSpy} />);

    const textInput = screen.getByPlaceholderText('Enter Code') as HTMLInputElement;
    fireEvent.change(textInput, { target: { value: '12345' } });

    expect(screen.getByText('Submit')).toBeInTheDocument();
    expect(textInput.value).toBe('12345');

    await userEvent.click(screen.getByText('Submit'));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Sales POC updated successfully',
      });
      expect(onSalesPocUpdateSpy).toHaveBeenCalled();
    });
  });
});
