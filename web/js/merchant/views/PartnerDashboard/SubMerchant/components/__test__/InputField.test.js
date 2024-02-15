import React from 'react';

import InputField from 'merchant/views/PartnerDashboard/SubMerchant/components/InputField';
import { render, screen } from 'test-utils';
const defaultProps = {
  value: 'text value',
  type: 'text',
  disabled: true,
  class: 'form-control',
};
describe('InputField', () => {
  test('should render with class prop', () => {
    render(<InputField {...defaultProps} />, {});
    expect(screen.getByDisplayValue('text value')).toBeVisible();
    expect(screen.getByDisplayValue('text value')).toHaveClass('form-control');
  });
});
