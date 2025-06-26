import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import BentoCard from '../BentoCard';
import * as track from '../track';
import { versionReleases } from '../data';

jest.mock('../track', () => ({
  trackProductVersioningWidgetClicked: jest.fn(),
}));
const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

const props = {
  ...versionReleases[0],
  image: versionReleases[0].imgLarge,
  flipped: false,
  onFlip: jest.fn(),
  onClose: jest.fn(),
  isMobile: false,
  boxType: 'large' as 'large' | 'small',
  ctaText: 'Click Me',
  ctaLink: '/dummy',
};

describe('BentoCard CTA', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('calls trackProductVersioningWidgetClicked on CTA click', () => {
    render(<BentoCard {...props} />);
    fireEvent.click(screen.getByText('Click Me'));
    expect(track.trackProductVersioningWidgetClicked).toHaveBeenCalledWith({
      productClicked: props.id,
      clickButtonName: 'Click Me',
    });
  });

  it('opens window for full URL', () => {
    const openSpy = jest.spyOn(window, 'open').mockImplementation(() => null);
    render(<BentoCard {...props} ctaLink="https://example.com" />);
    fireEvent.click(screen.getByText('Click Me'));
    expect(openSpy).toHaveBeenCalledWith('https://example.com', '_blank', 'noopener,noreferrer');
    openSpy.mockRestore();
  });

  it('navigates for relative link', () => {
    render(<BentoCard {...props} ctaLink="/relative" />);
    fireEvent.click(screen.getByText('Click Me'));
    expect(mockNavigate).toHaveBeenCalledWith('/relative');
  });
}); 