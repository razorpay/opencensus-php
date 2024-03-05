import React from 'react';
import { normalizeUrl } from '../ajax';
import { render, screen } from '@dashboard/shared-utils/services/test/test-utils';

describe('Ajax util', () => {
  test('should remove trailing slashes', () => {
    expect(normalizeUrl('https://example.com/')).toBe('https://example.com');
  });
  test('should replace multiple slashes with a single one', () => {
    expect(normalizeUrl('https://example.com//path///to//resource')).toBe(
      'https://example.com/path/to/resource',
    );
  });
  test('should not replace double slash after protocol', () => {
    expect(normalizeUrl('https://example.com')).toBe('https://example.com');
  });
  test('should handle URLs without a protocol', () => {
    expect(normalizeUrl('example.com//path///to//resource')).toBe('example.com/path/to/resource');
  });
  test('should render URLs properly', () => {
    render(<div>{normalizeUrl('https://example.com//path///to//resource')}</div>);
    expect(screen.getByText('https://example.com/path/to/resource')).toBeInTheDocument();
  });
});
