import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import BannersList from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/BannerList';
import { bannerData } from './mockData';

describe('Upload Banner Drawer', () => {
  const handleReorder = jest.fn();
  const handleToggle = jest.fn();
  const handleDeleteImage = jest.fn();
  const handleReplaceImage = jest.fn();

  it('render banner list with banner data', () => {
    render(
      <BannersList
        bannerData={bannerData}
        onReorder={handleReorder}
        onToggleEnabled={handleToggle}
        handleDeleteImage={handleDeleteImage}
        handleReplaceImage={handleReplaceImage}
        isMobile={false}
      />,
    );
    expect(screen.getByText(/Your added banner images/i)).toBeInTheDocument();

    expect(
      screen.getByAltText(`${bannerData[0].position.toString()}-storefront-banner`),
    ).toBeInTheDocument();
    expect(
      screen.getByAltText(`${bannerData[1].position.toString()}-storefront-banner`),
    ).toBeInTheDocument();
    expect(
      screen.getByAltText(`${bannerData[2].position.toString()}-storefront-banner`),
    ).toBeInTheDocument();
  });

  it('handle edit click on first banner', () => {
    render(
      <BannersList
        bannerData={bannerData}
        onReorder={handleReorder}
        onToggleEnabled={handleToggle}
        handleDeleteImage={handleDeleteImage}
        handleReplaceImage={handleReplaceImage}
        isMobile={false}
      />,
    );

    const firstBannerEdit = screen.getByLabelText(
      `${bannerData[0].position.toString()}-storefront-edit-icon`,
    );
    fireEvent.click(firstBannerEdit);

    expect(
      screen.getByTestId(`${bannerData[0].position.toString()}-replace-banner`),
    ).toBeInTheDocument();
    expect(
      screen.getByTestId(`${bannerData[0].position.toString()}-delete-banner`),
    ).toBeInTheDocument();
  });

  it('handle toggle on first banner', () => {
    render(
      <BannersList
        bannerData={bannerData}
        onReorder={handleReorder}
        onToggleEnabled={handleToggle}
        handleDeleteImage={handleDeleteImage}
        handleReplaceImage={handleReplaceImage}
        isMobile={false}
      />,
    );

    const firstBannerEdit = screen.getByLabelText(
      `${bannerData[0].position.toString()}-storefront-toggle`,
    );
    fireEvent.click(firstBannerEdit);

    expect(handleToggle).toHaveBeenCalledWith(false, bannerData[0]);
  });

  it('render delete modal on delete of first banner', () => {
    render(
      <BannersList
        bannerData={bannerData}
        onReorder={handleReorder}
        onToggleEnabled={handleToggle}
        handleDeleteImage={handleDeleteImage}
        handleReplaceImage={handleReplaceImage}
        isMobile={false}
      />,
    );

    const firstBannerEdit = screen.getByLabelText(
      `${bannerData[0].position.toString()}-storefront-edit-icon`,
    );
    fireEvent.click(firstBannerEdit);

    const deleteEl = screen.getByTestId(`${bannerData[0].position.toString()}-delete-banner`);
    fireEvent.click(deleteEl);

    expect(handleDeleteImage).toHaveBeenCalledWith(bannerData[0]);
  });
});
