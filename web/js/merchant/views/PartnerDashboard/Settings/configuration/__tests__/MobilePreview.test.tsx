import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'common/services/test/test-utils';
import { MobilePreviewDetails } from 'merchant/views/PartnerDashboard/Settings/configuration/MobilePreviewDetails';
import { MobilePreviewWelcome } from 'merchant/views/PartnerDashboard/Settings/configuration/MobilePreviewWelcome';
import { previewProps } from 'merchant/views/PartnerDashboard/Settings/configuration/__tests__/mocks/fixtures';

describe('MobilePreviewWelcome', () => {
  test('should renders correctly with props', () => {
    render(<MobilePreviewWelcome {...previewProps} />);
    const textElement = screen.getByText(previewProps.brandName);
    expect(textElement).toBeInTheDocument();

    const styledBtn = screen.getByText('Get Started');
    expect(styledBtn).toHaveStyle(`background: ${previewProps.brandColor}`);

    const imgElement = screen.getByAltText('logo-mobile');
    expect(imgElement).toBeInTheDocument();
  });

  test('should render without brand logo', () => {
    render(<MobilePreviewWelcome {...previewProps} uploadLogo="" />);

    const imgElement = screen.queryByAltText('logo-mobile');
    expect(imgElement).not.toBeInTheDocument();
  });
});

describe('MobilePreviewDetails', () => {
  test('should render correctly with props', () => {
    render(<MobilePreviewDetails {...previewProps} />);

    const styledBtn = screen.getByText('Continue');
    expect(styledBtn).toHaveStyle(`background: ${previewProps.brandColor}`);

    const footerLogo = screen.getByAltText('razorpay');
    expect(footerLogo).toBeInTheDocument();
  });

  test('should render correctly without logo', () => {
    render(<MobilePreviewDetails {...previewProps} rzpLogo="" />);

    const imgElement = screen.queryByAltText('razorpay');
    expect(imgElement).toBeInTheDocument();
  });
});
