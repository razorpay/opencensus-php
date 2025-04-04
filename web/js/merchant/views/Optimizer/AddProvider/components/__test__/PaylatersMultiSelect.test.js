import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import PaylatersMultiSelect from 'merchant/views/Optimizer/AddProvider/components/PaylatersMultiSelect';
import { render, screen } from 'test-utils';

describe('PaylatersMultiSelect Component', () => {
  const props = {
    isFormEdit: false,
    paylaterOptions: ['getsimpl', 'simpl_pay_in_3'],
    paylaterSelected: ['getsimpl', 'simpl_pay_in_3'],
  };

  test('should render without any errors', () => {
    expect(() => <PaylatersMultiSelect {...props} />).not.toThrowError();
  });

  test('should show "Paylaters" label', () => {
    render(<PaylatersMultiSelect {...props} />);
    expect(screen.getByText('Paylaters')).toBeInTheDocument();
  });

  test('should show comma separated selected paylaters', () => {
    render(<PaylatersMultiSelect {...props} />);
    expect(screen.getByText('Simpl, Simpl Pay in 3')).toBeInTheDocument();
  });
});
