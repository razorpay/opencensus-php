import { LayoutIcon } from '@razorpay/blade/components';
import { getL1ProductItems } from 'merchant/components/SidebarV2/utils/Products';

describe('SidebarV2 utils -> Products', () => {
  const mockData = [
    {
      title: 'Payments',
      href: '/route/payments',
      icon: LayoutIcon,
    },
    {
      title: 'Transfers',
      href: '/route/transfers',
      icon: LayoutIcon,
    },
    {
      title: 'Platform Fee',
      href: '/route/platformfee',
      icon: LayoutIcon,
    },
    {
      title: 'Reversals',
      href: '/route/reversals',
      icon: LayoutIcon,
    },
    {
      title: 'Accounts',
      href: '/route/accounts',
      icon: LayoutIcon,
      items: [
        {
          title: 'Razorpay',
          href: '/route/accounts',
        },
        {
          title: 'Optimizer',
          href: '/route/optimizer/accounts',
        },
      ],
    },
  ];

  const mockDataBatchUpload = [
    {
      title: 'Batch Upload',
      href: '/route/batchuploads',
      icon: LayoutIcon,
    },
  ];

  test('should return the correct route product list for curlec', () => {
    const result = getL1ProductItems('route', {
      isOptimizerEnabled: true,
      isOptimizerRouteEnabled: true,
      isOrgCurlec: true,
    });
    expect(result).toEqual(mockData);
  });

  test('should return the correct route product list for non-curlec', () => {
    const result = getL1ProductItems('route', {
      isOptimizerEnabled: true,
      isOptimizerRouteEnabled: true,
      isOrgCurlec: false,
    });
    expect(result).toEqual([...mockData, ...mockDataBatchUpload]);
  });

  test('should return empty array for route while optimizer not enabled', () => {
    const result = getL1ProductItems('route', {
      isOptimizerEnabled: false,
      isOptimizerRouteEnabled: false,
    });
    expect(result).toEqual([]);
  });

  test('should return empty array for route while optimizer route not enabled', () => {
    const result = getL1ProductItems('route', {
      isOptimizerEnabled: true,
      isOptimizerRouteEnabled: false,
    });
    expect(result).toEqual([]);
  });
});
