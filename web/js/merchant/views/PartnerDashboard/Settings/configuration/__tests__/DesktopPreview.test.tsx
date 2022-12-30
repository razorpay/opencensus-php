import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'common/services/test/test-utils';
import { DesktopPreviewDetails } from 'merchant/views/PartnerDashboard/Settings/configuration/DesktopPreviewDetails';
import { DesktopPreviewWelcome } from 'merchant/views/PartnerDashboard/Settings/configuration/DesktopPreviewWelcome';
import { previewProps } from 'merchant/views/PartnerDashboard/Settings/configuration/__tests__/mocks/fixtures';

describe('DesktopPreviewWelcome', () => {
  test('should render correctly with props', () => {
    render(<DesktopPreviewWelcome {...previewProps} />);
    const textElement = screen.getByText(previewProps.brandName);
    expect(textElement).toBeInTheDocument();

    const styledBtn = screen.getByText('Get Started');
    expect(styledBtn).toHaveStyle(`background: ${previewProps.brandColor}`);

    const imgElement = screen.getByAltText('logo-desktop');
    expect(imgElement).toBeInTheDocument();

    const footerLogo = screen.getByAltText('rzp');
    expect(footerLogo).toBeInTheDocument();
  });

  test('should render without brand logo', () => {
    render(<DesktopPreviewWelcome {...previewProps} uploadLogo="" />);
    const imgElement = screen.queryByAltText('logo-desktop');
    expect(imgElement).not.toBeInTheDocument();
  });
});

describe('DesktopPreviewDetails', () => {
  test('should render correctly with props', () => {
    render(<DesktopPreviewDetails {...previewProps} />);
    const textElement = screen.getByText(previewProps.brandName);
    expect(textElement).toBeInTheDocument();

    const styledBtn = screen.getByText('Continue');
    expect(styledBtn).toHaveStyle(`background: ${previewProps.brandColor}`);

    const imgElement = screen.getByAltText('logo-top');
    expect(imgElement).toBeInTheDocument();

    const footerLogo = screen.getByAltText('rzp');
    expect(footerLogo).toBeInTheDocument();
  });

  test('should render correctly without logo', () => {
    render(<DesktopPreviewDetails {...previewProps} uploadLogo="" />);

    const imgElement = screen.queryByAltText('logo-top');
    expect(imgElement).not.toBeInTheDocument();
  });
});
