// web/js/merchant/containers/Home/Versioning/__tests__/BentoBox.test.tsx

import '@testing-library/jest-dom/extend-expect';
import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import BentoBox from 'merchant/containers/Home/Versioning/BentoBox';
import { versionReleases } from 'merchant/containers/Home/Versioning/data';
import { trackProductVersioningWidgetFlipAction, trackProductVersioningWidgetClicked } from 'merchant/containers/Home/Versioning/track';
import * as utils from '../utils';

// Mock the tracking functions
jest.mock('../track', () => ({
  trackProductVersioningWidgetFlipAction: jest.fn(),
  trackProductVersioningWidgetClicked: jest.fn(),
}));

// Only mock useDeviceType
jest.mock('../utils', () => {
  const actual = jest.requireActual('../utils');
  return {
    ...actual,
    useDeviceType: jest.fn(() => 'desktop'),
  };
});

const renderApp = () => {
  return render(
    <BentoBox bentoCards={versionReleases} cardSetType="versionUpdates" />
  );
};

describe('BentoBox', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (utils.useDeviceType as jest.Mock).mockImplementation(() => 'desktop');
  });

  test('renders the grid layout', () => {
    renderApp();

    expect(screen.getByTestId('bento-box-grid')).toBeInTheDocument();
  });

  test('should render all bento cards', () => {
    renderApp();
    versionReleases.forEach(card => {
      expect(screen.getByTestId(`bento-card-${card.id}`)).toBeInTheDocument();
    });
  });

  test('should handle card flip', () => {
    renderApp();
    const firstCard = versionReleases[0];
    const flipButton = screen.getByTestId(`flip-${firstCard.id}`);

    fireEvent.click(flipButton);
    expect(trackProductVersioningWidgetFlipAction).toHaveBeenCalledWith({
      productClicked: firstCard.id,
      actionName: 'Flip',
      cardSetType: 'versionUpdates',
    });
  });

  test('should handle card close on mobile', () => {
    (utils.useDeviceType as jest.Mock).mockImplementation(() => 'mobile');
    renderApp();
    const firstCard = versionReleases[0];
    const closeButton = screen.getByTestId(`close-${firstCard.id}`);

    fireEvent.click(closeButton);
    expect(trackProductVersioningWidgetFlipAction).toHaveBeenCalledWith({
      productClicked: firstCard.id,
      actionName: 'Close',
      cardSetType: 'versionUpdates',
    });
  });

  test('should render correct grid layout for desktop', () => {
    renderApp();
    const container = screen.getByTestId('bento-box-grid');
    const computedStyle = window.getComputedStyle(container);
    expect(computedStyle.display).toBe('grid');
    expect(computedStyle.gridTemplateColumns.replace(/\s/g, '')).toBe('repeat(4,1fr)');
  });

  test('should render correct grid layout for mobile', () => {
    (utils.useDeviceType as jest.Mock).mockImplementation(() => 'mobile');
    renderApp();
    const container = screen.getByTestId('bento-box-grid');
    const computedStyle = window.getComputedStyle(container);
    expect(computedStyle.display).toBe('grid');
    expect(computedStyle.gridTemplateColumns.replace(/\s/g, '')).toBe('1fr');
  });

  test('renders minimal BentoBox', () => {
    renderApp();
    expect(screen.getByTestId('bento-box-grid')).toBeInTheDocument();
  });
});