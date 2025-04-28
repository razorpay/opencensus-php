import { getCanonicalUrl, isDuplicateWebsite } from 'common/utils/rzp-utils';

describe('getCanonicalUrl', () => {
  it('should normalize URL by removing protocol', () => {
    expect(getCanonicalUrl('http://example.com')).toBe('example.com');
    expect(getCanonicalUrl('https://example.com')).toBe('example.com');
  });

  it('should add https protocol if not provided', () => {
    expect(getCanonicalUrl('example.com')).toBe('example.com');
  });

  it('should remove www prefix', () => {
    expect(getCanonicalUrl('www.example.com')).toBe('example.com');
    expect(getCanonicalUrl('http://www.example.com')).toBe('example.com');
  });

  it('should convert hostname to lowercase', () => {
    expect(getCanonicalUrl('EXAMPLE.com')).toBe('example.com');
    expect(getCanonicalUrl('ExAmPlE.CoM')).toBe('example.com');
  });

  it('should trim whitespace from hostname', () => {
    expect(getCanonicalUrl(' example.com ')).toBe('example.com');
  });

  it('should preserve path but remove trailing slashes', () => {
    expect(getCanonicalUrl('example.com/path')).toBe('example.com/path');
    expect(getCanonicalUrl('example.com/path/')).toBe('example.com/path');
    expect(getCanonicalUrl('example.com/path////')).toBe('example.com/path');
  });

  it('should preserve query parameters', () => {
    expect(getCanonicalUrl('example.com?param=value')).toBe('example.com?param=value');
    expect(getCanonicalUrl('example.com/path?param=value')).toBe('example.com/path?param=value');
    expect(getCanonicalUrl('example.com/path/?param=value&another=123')).toBe(
      'example.com/path?param=value&another=123',
    );
  });

  it('should return null for invalid URLs', () => {
    expect(getCanonicalUrl('invalid-url')).toBe(null);
    expect(getCanonicalUrl('')).toBe(null);
    expect(getCanonicalUrl('http://')).toBe(null);
  });

  it('should handle complex URLs correctly', () => {
    expect(getCanonicalUrl('https://www.EXAMPLE.com/path/to/page////?id=123&sort=asc')).toBe(
      'example.com/path/to/page?id=123&sort=asc',
    );
  });
});

describe('isDuplicateWebsite', () => {
  describe('with string input for existingWebsites', () => {
    it('should return true when URLs are duplicates (same canonical form)', () => {
      expect(isDuplicateWebsite('https://example.com', 'http://example.com')).toBe(true);
      expect(isDuplicateWebsite('www.example.com', 'example.com')).toBe(true);
      expect(isDuplicateWebsite('example.com/', 'example.com')).toBe(true);
    });

    it('should return false when URLs are different', () => {
      expect(isDuplicateWebsite('example.com', 'different.com')).toBe(false);
      expect(isDuplicateWebsite('example.com/path1', 'example.com/path2')).toBe(false);
    });

    it('should return false when URLs have different query parameters', () => {
      expect(isDuplicateWebsite('example.com?id=1', 'example.com?id=2')).toBe(false);
      expect(isDuplicateWebsite('example.com/path?param=1', 'example.com/path?param=2')).toBe(
        false,
      );
    });

    it('should return false when new website is invalid', () => {
      expect(isDuplicateWebsite('example.com', 'invalid-url')).toBe(false);
    });

    it('should return false when existing website is invalid', () => {
      expect(isDuplicateWebsite('invalid-url', 'example.com')).toBe(false);
    });
  });

  describe('with array input for existingWebsites', () => {
    it('should return true when new URL matches any URL in the array', () => {
      const existingWebsites = ['site1.com', 'site2.com', 'https://www.example.com'];
      expect(isDuplicateWebsite(existingWebsites, 'example.com')).toBe(true);
    });

    it('should return false when new URL does not match any URL in the array', () => {
      const existingWebsites = ['site1.com', 'site2.com', 'site3.com'];
      expect(isDuplicateWebsite(existingWebsites, 'example.com')).toBe(false);
    });

    it('should return false when array is empty', () => {
      expect(isDuplicateWebsite([], 'example.com')).toBe(false);
    });

    it('should handle case with multiple similar but not duplicate URLs', () => {
      const existingWebsites = ['example.com/path1', 'example.com/path2', 'example.com?id=1'];
      expect(isDuplicateWebsite(existingWebsites, 'example.com/path3')).toBe(false);
      expect(isDuplicateWebsite(existingWebsites, 'example.com?id=2')).toBe(false);
    });

    it('should handle case with mix of valid and invalid URLs in array', () => {
      const existingWebsites = ['invalid-url', 'site1.com', 'example.com'];
      expect(isDuplicateWebsite(existingWebsites, 'example.com')).toBe(true);
      expect(isDuplicateWebsite(existingWebsites, 'site1.com')).toBe(true);
      expect(isDuplicateWebsite(existingWebsites, 'different.com')).toBe(false);
    });
  });

  describe('edge cases', () => {
    it('should handle empty strings properly', () => {
      expect(isDuplicateWebsite('', 'example.com')).toBe(false);
      expect(isDuplicateWebsite('example.com', '')).toBe(false);
      expect(isDuplicateWebsite(['', 'site1.com'], 'site1.com')).toBe(true);
    });

    it('should handle case sensitivity correctly', () => {
      expect(isDuplicateWebsite('Example.Com', 'example.com')).toBe(true);
      expect(isDuplicateWebsite(['SITE1.com', 'Site2.Com'], 'site1.com')).toBe(true);
    });
  });
});
