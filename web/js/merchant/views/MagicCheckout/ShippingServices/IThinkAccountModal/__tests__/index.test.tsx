import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import IThinkModal from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal';

describe('testing integration modal component', () => {
  test('component should render properly', () => {
    render(<IThinkModal />);

    expect(screen.getByText('Link iThink Logistics account')).toBeInTheDocument();
  });

  test('should be able to click on skip instructions or enter credentials', async () => {
    render(<IThinkModal />);

    const skipCta = screen.getByText(/^Skip instructions?/i);

    await userEvent.click(skipCta);

    //after skip cta is clicked back cta would be visible
    const backCta = screen.getByText(/^Back?/i);
    await userEvent.click(backCta);

    const enterCredsCta = screen.getByText(/^Enter credentials?/i);
    await userEvent.click(enterCredsCta);

    expect(screen.getByText(/^API Key?/)).toBeInTheDocument();
  });
});
