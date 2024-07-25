import React from 'react';
import { useScreen } from '../useScreen';
import { render, screen } from 'apps/pos/src/services/test/test-utils';

jest.mock('@razorpay/blade/utils', () => ({
  useTheme: () => ({ theme: { breakpoints: { mobile: 0 } } }),
  useBreakpoint: () => ({ matchedDeviceType: 'mobile' }),
}));

const TestApp = () => {
  const { isMobile } = useScreen();
  return <div>{isMobile ? 'Mobile' : 'Desktop'}</div>;
};

describe('useScreen', () => {
  test('should return isMobile as true for mobile', () => {
    render(<TestApp />);
    expect(screen.getByText('Mobile')).toBeInTheDocument();
  });
});
