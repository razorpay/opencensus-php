import React from 'react';
import { render, screen } from 'test-utils';

import { GetSimplPoints } from 'merchant/views/Navigator/components/Provider/SeamlessComponents/GetSimplPoints';

describe('GetSimplPoints component', () => {
  test('should render GetSimplPoints without any errors', () => {
    expect(() => render(<GetSimplPoints />)).not.toThrowError();
  });

  test('should render the points for Simpl gateway', () => {
    render(<GetSimplPoints />);

    expect(
      screen.getByText(
        'Reach out to your relationship manager at Simpl to collect your gateway terminal ID to get started',
      ),
    ).toBeInTheDocument();
  });
});
