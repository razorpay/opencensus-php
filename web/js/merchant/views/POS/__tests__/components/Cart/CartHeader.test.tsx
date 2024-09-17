import React from 'react';
import { TrashIcon } from '@razorpay/blade/components';

import SoundboxImage from 'assets/pos/product-description/soundbox/cart-img.webp';
import CartHeader from 'merchant/views/POS/Cart/CartPanel/CartHeader';
import { render, screen, userEvent } from 'test-utils';

describe('<CartHeader/>', () => {
  test('should render CartHeader with product title', () => {
    render(<CartHeader cartImage={SoundboxImage} productTitle="Sample Product" />);
    expect(screen.getByText('Sample Product')).toBeInTheDocument();
  });
  test('should render OfferStrip when offerStrip prop is provided', () => {
    const offerStrip = {
      text: 'Special Offer',
      isPartnerPricing: true,
    };
    render(
      <CartHeader
        offerStrip={offerStrip}
        cartImage={SoundboxImage}
        productTitle="Sample Product"
      />,
    );
    expect(screen.getByTestId('offer-strip-text')).toHaveTextContent('Special Offer');
  });
  test('should not render OfferStrip when offerStrip prop is not provided', () => {
    render(<CartHeader cartImage={SoundboxImage} productTitle="Sample Product" />);
    expect(screen.queryByTestId('offer-strip-text')).not.toBeInTheDocument();
  });
  test('should render icon and call clickhandler', async () => {
    const iconClickHandler = jest.fn();
    render(
      <CartHeader
        productTitle="Sample Product"
        cartImage="sample-image-url.jpg"
        icon={TrashIcon}
        iconClickHandler={iconClickHandler}
      />,
    );
    await userEvent.click(screen.getByLabelText('product delete icon'));
    expect(iconClickHandler).toHaveBeenCalled();
  });
  test('should not render icon when icon prop is not passed', () => {
    render(<CartHeader productTitle="Sample Product" cartImage={SoundboxImage} />);
    expect(screen.queryByLabelText('product delete icon')).not.toBeInTheDocument();
  });
});
