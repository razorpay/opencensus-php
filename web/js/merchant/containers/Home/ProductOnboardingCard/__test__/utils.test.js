import { getHandleEntities } from 'merchant/containers/Home/ProductOnboardingCard/utils';

describe('ProductOnboardingCard - utils', () => {
  test('should return empty domain and slug if url is empty string', () => {
    const { domain, slug } = getHandleEntities('');

    expect(domain).toBe('');
    expect(slug).toBe('');
  });

  test('should return correct domain and slug if url is provided', () => {
    const { domain, slug, prefix } = getHandleEntities(
      'https://rzpme.np.razorpay.in/@pizzapalooza6839',
    );

    expect(domain).toBe('https://rzpme.np.razorpay.in/');
    expect(slug).toBe('pizzapalooza6839');
    expect(prefix).toBe('@');
  });
});
