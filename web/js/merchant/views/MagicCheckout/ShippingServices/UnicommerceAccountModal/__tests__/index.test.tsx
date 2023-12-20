import React from 'react';
import { render, screen } from 'test-utils';

import UnicommerceModal from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal';

describe('testing integration modal component', () => {
  test('component should render properly', () => {
    render(<UnicommerceModal />);

    expect(screen.getByText('Integrate with Unicommerce')).toBeInTheDocument();
  });
});
