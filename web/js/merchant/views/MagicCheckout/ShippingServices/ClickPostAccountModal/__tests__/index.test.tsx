import React from 'react';
import { render, screen } from 'test-utils';

import ClickpostModal from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal';

describe('testing integration modal component', () => {
  test('component should render properly', () => {
    render(<ClickpostModal />);

    expect(screen.getByText('Link Clickpost account')).toBeInTheDocument();
  });
});
