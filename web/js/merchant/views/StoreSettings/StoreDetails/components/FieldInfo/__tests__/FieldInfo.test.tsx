import React from 'react';

import FieldInfo from 'merchant/views/StoreSettings/StoreDetails/components/FieldInfo';
import { screen, render } from 'test-utils';

describe('FieldInfo', () => {
  test("should render 'FieldInfo' component as expected", () => {
    render(<FieldInfo label="Test Label" value="Test Value" enableCopyOption={false} />);

    expect(screen.getByText('Test Label')).toBeInTheDocument();
    expect(screen.getByText('Test Value')).toBeInTheDocument();

    // Copy option should not be available when 'enableCopyOption' prop is false
    expect(document.querySelector('.ClipboardCustom')).toBe(null);
  });

  test('should render value as link when URL is passed for value prop', () => {
    render(<FieldInfo enableCopyOption label="Test Label" value="https://www.razorpay.com" />);

    expect(screen.getByText('Test Label')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'https://www.razorpay.com' })).toBeInTheDocument();

    // Copy option should not be available when value is of type link even when 'enableCopyOption' prop is true
    expect(document.querySelector('.ClipboardCustom')).toBe(null);
  });
});
