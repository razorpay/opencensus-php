import React from 'react';
import { render, screen } from 'test-utils';
import CouponPreviewSection from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponPreview/CouponPreviewSection';

describe('CouponSection Component', () => {
  test('should render the title and list items correctly', () => {
    const title = 'Available Coupons';
    const lists = ['Coupon 1', 'Coupon 2', 'Coupon 3'];

    render(
      <CouponPreviewSection couponPreviewSectionTitle={title} couponPreviewSectionList={lists} />,
    );

    // Check if the title is rendered correctly
    expect(screen.getByText(title)).toBeInTheDocument();

    // Check if all list items are rendered correctly
    lists.forEach((list) => {
      expect(screen.getByText(list)).toBeInTheDocument();
    });
  });

  test('should render nothing when the lists array is empty', () => {
    const title = 'No Coupons';
    const lists = [];

    const { container } = render(
      <CouponPreviewSection couponPreviewSectionTitle={title} couponPreviewSectionList={lists} />,
    );
    // Ensure the component does not render anything
    expect(container.textContent).toBe('');
  });

  test('should apply correct styles to the title', () => {
    const title = 'Styled Title';
    const lists = ['Item 1'];

    render(
      <CouponPreviewSection couponPreviewSectionTitle={title} couponPreviewSectionList={lists} />,
    );

    const titleElement = screen.getByText(title);

    // Check if the styles are applied to the title
    expect(titleElement).toHaveStyle({
      fontWeight: '600',
      color: 'rgb(25, 25, 25)',
    });
  });
});
