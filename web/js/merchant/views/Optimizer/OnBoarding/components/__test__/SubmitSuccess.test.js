import React from 'react';
import { render, screen } from 'test-utils';

import { SubmitSuccess } from 'merchant/views/Optimizer/OnBoarding/components/SubmitSuccess';

const renderComponent = (props) => {
  return render(<SubmitSuccess {...props} />);
};

describe('Optimizer OnBoarding - SubmitSuccess', () => {
  test('Should render without errors', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should render correct texts', () => {
    renderComponent();
    const successImg = screen.getByRole('img');
    expect(successImg).toHaveAttribute('alt', 'success');
    expect(
      screen.getByText("Optimizer - Razorpay's AI powered payments router"),
    ).toBeInTheDocument();
    expect(screen.getByText('Request submitted successfully!')).toBeInTheDocument();
    expect(
      screen.getByText(
        "We sent you an email confirming the same. Sit back and relax, we'll get back to you in 48hrs.",
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Go to Optimizer blog' })).toBeInTheDocument();
  });
});
