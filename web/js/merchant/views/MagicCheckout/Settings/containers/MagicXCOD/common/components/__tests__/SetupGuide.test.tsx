import React from 'react';
import { render, screen } from 'test-utils';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { SetupGuide } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/SetupGuide';

describe('<SetupGuide />', () => {
  const props = {
    heading: 'Setup Guide',
    title: 'Excepteur irure sit incididunt consectetur.',
    description:
      'Aliquip exercitation est ad quis cillum cillum deserunt do nulla enim culpa commodo esse elit culpa.',
    thumbnail: '',
    video: '',
  };
  const App = () => (
    <BladeProvider themeTokens={bladeTheme}>
      <SetupGuide {...props} />
    </BladeProvider>
  );

  beforeEach(() => {
    render(<App />);
  });

  test('should render', () => {
    const heading = screen.getByText(/setup guide/i);
    expect(heading).toBeInTheDocument();
  });
});
