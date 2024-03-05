import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { mockData } from 'merchant/views/RizeMarketplace/Listing/__tests__/mocks/fixtures';
import {
  LargeDealCard,
  SmallDealCard,
} from 'merchant/views/RizeMarketplace/Listing/components/DealCard';
import {
  DealCardProps,
  SmallDealCardProps,
} from 'merchant/views/RizeMarketplace/Listing/components/DealCard/types';
import { useRizeMarketplaceStore } from 'merchant/views/RizeMarketplace/common/store';
import { getMarketplaceProductQueryKey } from 'merchant/views/RizeMarketplace/common/utils';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { mockCouponDealData, mockLinkDealData } from './mocks/fixtures';

const queryClient = new QueryClient();
for (const data of mockData) {
  queryClient.setQueryData(getMarketplaceProductQueryKey(data.slug), data);
}

const execCommand = jest.fn();
document.execCommand = execCommand;

const resetStore = (() => {
  const initial = useRizeMarketplaceStore.getState();
  return () => useRizeMarketplaceStore.setState(initial);
})();

const renderLargeDealCard = (props: DealCardProps): ReturnType<typeof render> => {
  resetStore();
  return render(
    <QueryClientProvider client={queryClient}>
      <LargeDealCard {...props} />
    </QueryClientProvider>,
  );
};

const renderSmallDealCard = (props: SmallDealCardProps): ReturnType<typeof render> => {
  resetStore();
  return render(
    <QueryClientProvider client={queryClient}>
      <SmallDealCard {...props} />
    </QueryClientProvider>,
  );
};

const getAvailDealButton = () =>
  screen.getByRole('button', {
    name: /avail deal/i,
  });

const testCouponCodeDealUI = async (): Promise<void> => {
  const availDealButton = getAvailDealButton();
  await userEvent.click(availDealButton);

  expect(screen.getByText(mockCouponDealData.coupon_code!)).toBeInTheDocument();

  const copyCodeButton = screen.getByRole('button', {
    name: /copy code/i,
  });
  await userEvent.click(copyCodeButton);
  /**
   * Uses execCommand to check if copy was initiated since navigator.clipboard APIs
   * don't run when `isSecureContext` is false. Unfortunately with this approach, we can't assert on copied value
   */
  expect(execCommand).toHaveBeenCalledWith('copy');
  expect(screen.getByText(/code copied!/i)).toBeInTheDocument();

  await waitFor(
    () => {
      expect(screen.queryByText(/code copied!/i)).toBe(null);
    },
    {
      timeout: 3500,
    },
  );

  const applyHereLink = screen.getByRole('link', {
    name: /apply here/i,
  }) as HTMLAnchorElement;

  await userEvent.click(applyHereLink);
  expect(applyHereLink).toHaveAttribute('href', mockCouponDealData.avail_link);
};

const testLinkDealUI = async (): Promise<void> => {
  const availDealButton = getAvailDealButton();
  await userEvent.click(availDealButton);

  const applyHereLink = screen.getByRole('link', {
    name: /apply here/i,
  }) as HTMLAnchorElement;

  await userEvent.click(applyHereLink);
  expect(applyHereLink).toHaveAttribute('href', mockLinkDealData.avail_link);
};

describe('LargeDealCard', () => {
  test('should render', () => {
    renderLargeDealCard({
      offer: mockCouponDealData.offer ?? '',
      slug: mockCouponDealData.slug,
      couponCode: mockCouponDealData.coupon_code,
      availLink: mockCouponDealData.avail_link ?? '',
    });

    const heading = screen.getByRole('heading', {
      name: mockCouponDealData.offer,
    });
    expect(heading).toBeInTheDocument();

    const availDealButton = getAvailDealButton();
    expect(availDealButton).toBeInTheDocument();
  });

  test('should render copy coupon UI when deal has coupon code', async () => {
    renderLargeDealCard({
      offer: mockCouponDealData.offer ?? '',
      slug: mockCouponDealData.slug,
      couponCode: mockCouponDealData.coupon_code,
      availLink: mockCouponDealData.avail_link ?? '',
    });

    await testCouponCodeDealUI();
  });

  test('should render "visit website" when deal only has avail link', async () => {
    renderLargeDealCard({
      offer: mockLinkDealData.offer ?? '',
      slug: mockLinkDealData.slug,
      availLink: mockLinkDealData.avail_link ?? '',
    });

    await testLinkDealUI();
  });

  test('should not render anything when product data does not exist in query cache', () => {
    const spiedOnConsoleError = jest.spyOn(console, 'error');
    spiedOnConsoleError.mockImplementation(() => {});

    renderLargeDealCard({
      offer: '',
      slug: 'non-existent-product',
      availLink: '',
    });

    expect(console.error).toHaveBeenCalled();
    expect(screen.queryByRole('heading')).toBe(null);
    spiedOnConsoleError.mockClear();
  });
});

describe('SmallDealCard', () => {
  const openSmallDealCardBottomSheet = async () => {
    const availDealButton = getAvailDealButton();
    expect(availDealButton).toBeInTheDocument();

    await userEvent.click(availDealButton);
    expect(screen.getByRole('heading', { name: /avail deal/i })).toBeInTheDocument();
  };

  test('should render deal with coupon code inline', async () => {
    renderSmallDealCard({
      offer: mockCouponDealData.offer ?? '',
      slug: mockCouponDealData.slug,
      couponCode: mockCouponDealData.coupon_code,
      availLink: mockCouponDealData.avail_link ?? '',
    });

    expect(
      screen.getByRole('heading', {
        name: mockCouponDealData.offer,
      }),
    ).toBeInTheDocument();

    await openSmallDealCardBottomSheet();
    await testCouponCodeDealUI();
  });

  test('should render deal with only avail link inline', async () => {
    renderSmallDealCard({
      offer: mockLinkDealData.offer ?? '',
      slug: mockLinkDealData.slug,
      availLink: mockLinkDealData.avail_link ?? '',
    });

    expect(
      screen.getByRole('heading', {
        name: mockLinkDealData.offer,
      }),
    ).toBeInTheDocument();

    await openSmallDealCardBottomSheet();
    await testLinkDealUI();
  });

  test('should render deal with coupon code sticky', async () => {
    renderSmallDealCard({
      offer: mockCouponDealData.offer ?? '',
      slug: mockCouponDealData.slug,
      couponCode: mockCouponDealData.coupon_code,
      availLink: mockCouponDealData.avail_link ?? '',
      isSticky: true,
    });

    expect(
      screen.getByRole('heading', {
        name: mockCouponDealData.offer,
      }),
    ).toBeInTheDocument();

    await openSmallDealCardBottomSheet();
    await testCouponCodeDealUI();
  });

  test('should render sticky deal card', async () => {
    renderSmallDealCard({
      offer: mockCouponDealData.offer ?? '',
      slug: mockCouponDealData.slug,
      couponCode: mockCouponDealData.coupon_code,
      availLink: mockCouponDealData.avail_link ?? '',
      isSticky: true,
    });

    expect(
      screen.getByRole('heading', {
        name: mockCouponDealData.offer,
      }),
    ).toBeInTheDocument();

    await openSmallDealCardBottomSheet();
    await testCouponCodeDealUI();
  });

  test('should not render anything when product data does not exist in query cache', () => {
    const spiedOnConsoleError = jest.spyOn(console, 'error');
    spiedOnConsoleError.mockImplementation(() => {});

    renderSmallDealCard({
      offer: '',
      slug: 'non-existent-product',
      availLink: '',
    });

    expect(console.error).toHaveBeenCalled();
    expect(screen.queryByRole('heading')).toBe(null);
    spiedOnConsoleError.mockClear();
  });
});
