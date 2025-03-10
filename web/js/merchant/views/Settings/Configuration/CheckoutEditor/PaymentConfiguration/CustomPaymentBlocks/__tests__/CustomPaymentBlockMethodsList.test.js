import React from 'react';
import { render, screen } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { CustomPaymentBlockMethodsList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlockMethodsList';

const mockList = [
  { slug: 'method1', isVisible: true, isSingleInstrument: false },
  { slug: 'method2', isVisible: false, isSingleInstrument: true },
  { slug: 'method3', isVisible: true, isSingleInstrument: true },
];

describe('CustomPaymentBlockMethodsList', () => {
  test('should render CustomPaymentBlockMethodsList component', () => {
    render(<CustomPaymentBlockMethodsList list={mockList} blockKey="block1" />);
    expect(screen.getByLabelText('method1')).toBeInTheDocument();
    expect(screen.getByLabelText('method2')).toBeInTheDocument();
    expect(screen.getByLabelText('method3')).toBeInTheDocument();
  });

  test('should generate sortable list correctly', () => {
    render(<CustomPaymentBlockMethodsList list={mockList} blockKey="block1" />);
    const sortableListItems = screen.getAllByRole('button', { name: /method/i });
    expect(sortableListItems.length).toBe(4);
  });

  test('should handle empty list correctly', () => {
    render(<CustomPaymentBlockMethodsList list={[]} blockKey="block1" />);
    const sortableListItems = screen.queryAllByRole('button', { name: /method/i });
    expect(sortableListItems.length).toBe(0);
  });

  test('should display correct visibility status', () => {
    render(<CustomPaymentBlockMethodsList list={mockList} blockKey="block1" />);
    expect(screen.getByLabelText('method1')).toBeVisible();
    expect(screen.getByLabelText('method2')).toBeVisible();
    expect(screen.getByLabelText('method3')).toBeVisible();
  });
});
