import React from 'react';

import SalesPocBanner from 'apps/pos/src/app/views/SelfServe/OrderDetails/SalesPocBanner';
import {
  fireEvent,
  render,
  screen,
  server,
  userEvent,
  waitFor,
} from 'apps/pos/src/services/test/test-utils';
import { updateSalePoc } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';

const mockedUsedNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
  useParams: () => ({
    orderId: 'KwxwAgItmmXdp',
  }),
}));

const onSalesPocUpdateSpy = jest.fn();

describe('Sales Poc Banner Component', () => {
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

    await waitFor(
      () => {
        expect(screen.getByText('Something went wrong! Please try again.')).toBeVisible();
      },
      { timeout: 5000 },
    );
  });

  test('handles form submission failure on receiving data value null', async () => {
    server.use(updateSalePoc({ isSuccess: true, noData: true }));
    render(<SalesPocBanner orderId="KwxwAgItmmXdp" onSalesPocUpdate={onSalesPocUpdateSpy} />);

    const textInput = screen.getByPlaceholderText('Enter Code') as HTMLInputElement;
    fireEvent.change(textInput, { target: { value: '12345' } });

    expect(screen.getByText('Submit')).toBeInTheDocument();
    expect(textInput.value).toBe('12345');

    const submitCTA = screen.getByRole('button', { name: 'Submit' });
    expect(submitCTA).toBeEnabled();
    submitCTA.click();

    await waitFor(
      () => {
        expect(screen.getByText('Something went wrong! Please try again.')).toBeVisible();
      },
      { timeout: 5000 },
    );
    expect(onSalesPocUpdateSpy).not.toHaveBeenCalled();
  });

  test('handles form submission success', async () => {
    server.use(updateSalePoc({ isSuccess: true }));
    render(<SalesPocBanner orderId="KwxwAgItmmXdp" onSalesPocUpdate={onSalesPocUpdateSpy} />);

    const textInput = screen.getByPlaceholderText('Enter Code') as HTMLInputElement;
    fireEvent.change(textInput, { target: { value: '12345' } });

    expect(screen.getByText('Submit')).toBeInTheDocument();
    expect(textInput.value).toBe('12345');

    await userEvent.click(screen.getByText('Submit'));

    await waitFor(
      () => {
        expect(screen.getByText('Sales POC updated successfully')).toBeVisible();
        expect(onSalesPocUpdateSpy).toHaveBeenCalled();
      },
      { timeout: 5000 },
    );
  });
});
