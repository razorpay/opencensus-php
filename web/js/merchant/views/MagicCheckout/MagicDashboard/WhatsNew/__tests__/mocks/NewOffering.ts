export const newOffering = {
  title: 'New Feature',
  description: 'This is a description of the new feature.',
  condition: () => true,
  image: new File(['dummy content'], 'example.png', { type: 'image/png' }),
  ctaLink: '/cta-link',
  externalLink: {
    href: 'https://example.com/know-more',
    label: 'Know More',
  },
  docLink: jest.fn().mockReturnValue('https://example.com/doc-link'),
};

export const couponsTitle = 'All new Coupons360';

export const quickBuyTitle = 'Ultra-fast checkout with QuickBuy';
