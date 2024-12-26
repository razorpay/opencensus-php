import DiscountOffered from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/DiscountOfferedWidget.tsx';

import { COUPON_NAMES } from '../../constants';

import { render, screen, waitFor } from 'test-utils';

describe('Freebie Item Coupon', () => {
  jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useParams: () => ({
      couponName: 'freebie_item',
    }),
  }));

  test('Should not render Additional Products Offered Section as freebie item is capped at 1 Qty', async () => {
    render(<DiscountOffered couponName={COUPON_NAMES.FREEBIE_ITEM} />);
    await waitFor(() => {
      expect(screen.getByText('Discount Offered')).toBeInTheDocument();
      expect(screen.queryByText('Additional Products Offered')).not.toBeInTheDocument();
    });
  });

  test('Should not render Discount Type section as freebie item is always of Discount type: free', async () => {
    render(<DiscountOffered couponName={COUPON_NAMES.FREEBIE_ITEM} />);
    await waitFor(() => {
      expect(screen.queryByText('Discount type')).not.toBeInTheDocument();
      expect(screen.queryByText('Monetary discount')).not.toBeInTheDocument();
      expect(screen.queryByText('Fixed Discount')).not.toBeInTheDocument();
      expect(screen.queryByText('Free')).not.toBeInTheDocument();
      expect(screen.queryByText('Percentage discount')).not.toBeInTheDocument();
    });
  });

  test('Should render callout w.r.t freebie item selection restrictions', async () => {
    render(<DiscountOffered couponName={COUPON_NAMES.FREEBIE_ITEM} />);
    await waitFor(() => {
      expect(
        screen.getByText(
          'Free item is automatically added to the cart when purchase requirements are met',
        ),
      ).toBeInTheDocument();
    });
  });
});

describe('Non Freebie Item Coupon - BxGy Coupon', () => {
  jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-route-dom'),
    useParams: () => ({
      couponName: 'buyx_gety',
    }),
  }));

  test('Should render Additional Products Offered Section', async () => {
    render(<DiscountOffered couponName={COUPON_NAMES.BUYX_GETY} />);
    await waitFor(() => {
      expect(screen.getByText('Discount Offered')).toBeInTheDocument();
      expect(screen.queryByText('Additional Products Offered')).toBeInTheDocument();
    });
  });

  test('Should render Discount Type section', async () => {
    render(<DiscountOffered couponName={COUPON_NAMES.BUYX_GETY} />);
    await waitFor(() => {
      expect(screen.queryByText('Discount type')).toBeInTheDocument();
      expect(screen.queryByText('Monetary discount')).toBeInTheDocument();
      expect(screen.queryByText('Fixed Discount')).toBeInTheDocument();
      expect(screen.queryByText('Discount amount')).toBeInTheDocument();
    });
  });

  test('Should not render callout w.r.t freebie item selection restrictions', async () => {
    render(<DiscountOffered couponName={COUPON_NAMES.BUYX_GETY} />);
    await waitFor(() => {
      expect(
        screen.queryByText(
          'Free item is automatically added to the cart when purchase requirements are met',
        ),
      ).not.toBeInTheDocument();
    });
  });
});
