import React from 'react';
import { render } from '@testing-library/react';
import { Badge } from '@razorpay/blade/components';
import { getTitleSuffixComponent } from '../TitleSuffixRenderer';
import type { TitleSuffixConfig, BadgeConfig } from '../Products';

jest.mock('@razorpay/blade/components', () => ({
  Badge: jest.fn(() => <div data-testid="mocked-badge">Mocked Badge</div>),
}));

describe('getTitleSuffixComponent', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should return undefined when no config is provided', () => {
    const result = getTitleSuffixComponent();
    expect(result).toBeUndefined();
  });

  it('should return undefined for unknown component type', () => {
    const config = {
      componentType: 'Unknown',
      props: {},
    };
    const result = getTitleSuffixComponent(config);
    expect(result).toBeUndefined();
  });

  it('should return a Badge component with correct props', () => {
    const badgeConfig: TitleSuffixConfig = {
      componentType: 'Badge',
      props: {
        color: 'positive',
        size: 'small',
        children: 'New',
      },
    };

    const result = getTitleSuffixComponent(badgeConfig);

    const { getByTestId } = render(<>{result}</>);

    expect(Badge).toHaveBeenCalledWith(
      expect.objectContaining({
        color: 'positive',
        size: 'small',
        children: 'New',
      }),
      expect.anything(),
    );

    expect(getByTestId('mocked-badge')).toBeInTheDocument();
  });

  it('should return undefined for a non-componentType config', () => {
    const config: Partial<BadgeConfig> = {
      text: 'Some text',
      color: 'primary' as BadgeConfig['color'],
    };

    const result = getTitleSuffixComponent(config as BadgeConfig);
    expect(result).toBeUndefined();
  });
});
