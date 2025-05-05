import { isPopularWebsite } from '@libs/shared-utils';

describe('isPopularWebsite', () => {
  it('should identify popular websites correctly', () => {
    // Test popular websites
    expect(isPopularWebsite('google.com')).toBe(true);
    expect(isPopularWebsite('www.google.com')).toBe(true);
    expect(isPopularWebsite('https://google.com')).toBe(true);
    expect(isPopularWebsite('https://www.google.com')).toBe(true);
    expect(isPopularWebsite('http://google.com/search')).toBe(true);

    expect(isPopularWebsite('facebook.com')).toBe(true);
    expect(isPopularWebsite('instagram.com')).toBe(true);
    expect(isPopularWebsite('youtube.com')).toBe(true);
    expect(isPopularWebsite('twitter.com')).toBe(true);
    expect(isPopularWebsite('paytm.com')).toBe(true);
    expect(isPopularWebsite('phonepe.com')).toBe(true);
    expect(isPopularWebsite('pay.google.com')).toBe(true);
    expect(isPopularWebsite('bharatpe.com')).toBe(true);
    expect(isPopularWebsite('razorpay.com')).toBe(true);
    expect(isPopularWebsite('example.com')).toBe(true);
  });

  it('should handle URLs with paths and query parameters', () => {
    expect(isPopularWebsite('facebook.com/profile')).toBe(true);
    expect(isPopularWebsite('youtube.com/watch?v=12345')).toBe(true);
    expect(isPopularWebsite('razorpay.com/payments/checkout')).toBe(true);
  });

  it('should not identify non-popular websites', () => {
    expect(isPopularWebsite('mywebsite.com')).toBe(false);
    expect(isPopularWebsite('custom-domain.com')).toBe(false);
    expect(isPopularWebsite('my-business-site.com')).toBe(false);
    expect(isPopularWebsite('https://small-business.net')).toBe(false);
  });

  it('should handle exclusions correctly', () => {
    // play.google.com is excluded even though google.com is in the popular list
    expect(isPopularWebsite('play.google.com')).toBe(false);
    expect(isPopularWebsite('https://play.google.com')).toBe(false);
    expect(isPopularWebsite('play.google.com/store/apps')).toBe(false);
  });

  it('should handle subdomains correctly', () => {
    expect(isPopularWebsite('mail.google.com')).toBe(true); // Subdomains of google.com should match
    expect(isPopularWebsite('maps.google.com')).toBe(true);

    // Specific case for exclusions
    expect(isPopularWebsite('play.google.com')).toBe(false); // This is specifically excluded
  });

  it('should handle invalid or empty inputs', () => {
    expect(isPopularWebsite('')).toBe(false);
    expect(isPopularWebsite(null)).toBe(false);
    expect(isPopularWebsite(undefined)).toBe(false);
  });

  it('should handle edge cases', () => {
    // Partial matches that shouldn't be flagged
    expect(isPopularWebsite('notgoogle.com')).toBe(false);
    expect(isPopularWebsite('myfacebook.com')).toBe(false);
    expect(isPopularWebsite('youtube.community')).toBe(false);

    // These should match
    expect(isPopularWebsite('subdomain.google.com')).toBe(true);

    // Case sensitivity
    expect(isPopularWebsite('GoOgLe.CoM')).toBe(true);
    expect(isPopularWebsite('FACEBOOK.COM')).toBe(true);
  });
});
