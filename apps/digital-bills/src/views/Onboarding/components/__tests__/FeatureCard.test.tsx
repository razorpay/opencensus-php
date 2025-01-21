import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import FeatureCard from '@apps/digital-bills/src/views/Onboarding/components/FeatureCard';

describe('FeatureCard', () => {
  test('should render the FeatureCard component', () => {
    const { getByText, getByRole } = renderWithWrappers(
      <FeatureCard title="Test Title" description="Test Description" image="/" />,
    );
    expect(getByText('Test Title')).toBeInTheDocument();
    expect(getByText('Test Description')).toBeInTheDocument();
    expect(getByRole('img')).toBeInTheDocument();
  });
});
