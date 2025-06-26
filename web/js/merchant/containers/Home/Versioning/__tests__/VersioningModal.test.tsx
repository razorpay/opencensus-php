import React from 'react';
import { render, screen } from 'test-utils';
import VersioningModal from '../VersioningModal';
import * as track from '../track';
import { versioningUpgradeConfig, versioningCatchUpConfig } from '../data';

describe('VersioningModal', () => {
  const closeModal = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(track, 'trackProductVersioningWidgetLoaded').mockImplementation(jest.fn());
  });

  it('renders without crashing when open', () => {
    render(<VersioningModal isOpen={true} closeModal={closeModal} />);

    // Dynamically check for text content from versioningUpgradeConfig
    versioningUpgradeConfig.mobile.heading
      .filter((segment) => segment.type === 'text')
      .forEach((textSegment) => {
        expect(screen.getByText(textSegment.content.trim())).toBeInTheDocument();
      });

    // Dynamically check for text content from versioningCatchUpConfig
    versioningCatchUpConfig.desktop.heading
      .filter((segment) => segment.type === 'text')
      .forEach((textSegment) => {
        expect(screen.getByText(textSegment.content.trim())).toBeInTheDocument();
      });

    // Check that body text is rendered (length > 0)
    const bodyTexts = screen.getAllByText(versioningUpgradeConfig.desktop.body.text);
    expect(bodyTexts.length).toBeGreaterThan(0);
  });

  it('calls trackProductVersioningWidgetLoaded when opened', () => {
    render(<VersioningModal isOpen={true} closeModal={closeModal} />);
    expect(track.trackProductVersioningWidgetLoaded).toHaveBeenCalled();
  });

  it('does not call trackProductVersioningWidgetLoaded when closed', () => {
    render(<VersioningModal isOpen={false} closeModal={closeModal} />);
    expect(track.trackProductVersioningWidgetLoaded).not.toHaveBeenCalled();
  });

  it('displays the correct section card content', () => {
    render(<VersioningModal isOpen={true} closeModal={closeModal} />);

    // Dynamically check that both configs' heading text content is rendered
    const allTextSegments = [
      ...versioningUpgradeConfig.desktop.heading.filter((segment) => segment.type === 'text'),
      ...versioningCatchUpConfig.desktop.heading.filter((segment) => segment.type === 'text'),
    ];

    allTextSegments.forEach((textSegment) => {
      expect(screen.getByText(textSegment.content.trim())).toBeInTheDocument();
    });

    // Check that body text is rendered (length > 0)
    const bodyTexts = screen.getAllByText(versioningUpgradeConfig.desktop.body.text);
    expect(bodyTexts.length).toBeGreaterThan(0);
  });
});
