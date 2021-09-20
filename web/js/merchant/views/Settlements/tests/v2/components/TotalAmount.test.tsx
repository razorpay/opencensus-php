import React from 'react';
import TotalAmount from 'merchant/views/Settlements/v2/components/TotalAmount';
import { fireEvent, render, screen, waitFor } from 'test-utils';

test('should show debit amount', () => {
  render(<TotalAmount type="debit" isNew />, {});

  expect(screen.getByText(/Total debit amount/)).toBeInTheDocument();
});

test('should show credit amount', () => {
  render(<TotalAmount type="credit" isNew />, {});

  expect(screen.getByText(/Total credit amount/)).toBeInTheDocument();
});

test('should show settled amount', () => {
  render(<TotalAmount type="settled" isNew />, {});

  expect(screen.getByText(/Total settled amount/)).toBeInTheDocument();
});

test('should show tooltip on mouseenter', async () => {
  const { container, getByTestId } = render(<TotalAmount type="settled" isNew />, {});

  expect(getByTestId('total-amount-popover')).not.toHaveClass('show');

  fireEvent.mouseEnter(container.querySelector('.i-info-circle') || window);

  await waitFor(() => {
    expect(getByTestId('total-amount-popover')).toHaveClass('show');
  });
});
