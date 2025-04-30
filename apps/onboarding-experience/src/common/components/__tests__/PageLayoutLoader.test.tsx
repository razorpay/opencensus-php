import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import { PageLayoutLoader, CardLoader, BannerLoader } from '../PageLayoutLoader';

describe('CardLoader', () => {
  it('renders correctly with proper structure', () => {
    renderWithWrappers(<CardLoader />);

    expect(screen.getByTestId('card-loader')).toBeInTheDocument();
  });
});

describe('BannerLoader', () => {
  it('renders correctly with proper structure', () => {
    renderWithWrappers(<BannerLoader />);

    expect(screen.getByTestId('banner-loader')).toBeInTheDocument();
  });
});

describe('PageLayoutLoader', () => {
  it('renders correctly with proper structure', () => {
    renderWithWrappers(<PageLayoutLoader />);

    expect(screen.getByTestId('page-layout-loader')).toBeInTheDocument();
  });

  it('renders responsive elements correctly', () => {
    renderWithWrappers(<PageLayoutLoader />);

    // Verify mobile specific elements exist
    expect(screen.getByTestId('mobile-section-title')).toBeInTheDocument();

    // Verify desktop specific elements exist
    expect(screen.getByTestId('desktop-section-title-1')).toBeInTheDocument();
  });
});
