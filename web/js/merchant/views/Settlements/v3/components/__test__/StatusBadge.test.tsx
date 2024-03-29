import React from 'react';
import { render, screen } from 'test-utils';
import StatusBadge from 'merchant/views/Settlements/v3/components/StatusBadge';
import { VariantMap } from 'merchant/views/Settlements/v3/constants/info';
import { BADGE_INFO } from 'merchant/views/Settlements/components/utils';

jest.mock('common/ui/Popover', () => ({
  __esModule: true,
  default: ({ children }) => children,
  PopoverBody: ({ children }) => children,
}));

jest.mock('@razorpay/blade/components', () =>
  Object.assign({
    __esModule: true,
    ...jest.requireActual('@razorpay/blade/components'),
    Badge: ({ color, icon }) => {
      return (
        <>
          <span data-testid="color">{color}</span>
          {icon({})}
        </>
      );
    },
  }),
);

describe('StatusBadge', () => {
  test.each([
    [VariantMap.FAILED, BADGE_INFO.FAILED, 'failed'],
    [VariantMap.CREATED, BADGE_INFO.CREATED, 'created'],
    [VariantMap.PROCESSED, BADGE_INFO.PROCESSED, 'processed'],
  ])('should render %s variant and %s tooltip when status is %s', (variant, tooltip, status) => {
    render(<StatusBadge status={status} />);
    expect(screen.getByTestId('color')).toHaveTextContent(variant);
    expect(screen.getByText(tooltip)).toBeInTheDocument();
  });
});
