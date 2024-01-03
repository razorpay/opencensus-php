import React from 'react';

import ProductGallery from 'merchant/views/POS/ProductDescription/ProductGallery/ProductGallery';
import ProductGalleryCarousel from 'merchant/views/POS/ProductDescription/ProductGallery/ProductGalleryCarousel';
import Thumbnails from 'merchant/views/POS/ProductDescription/ProductGallery/Thumbnails';
import { MOCK_PRODUCT } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock('react-responsive-carousel', () => ({
  ...(jest.requireActual('react-responsive-carousel') as Record<string, string>),
  Carousel: ({ children }) => <div>{children}</div>,
}));

describe('<ProductGallery/>', () => {
  beforeEach(() => {
    const mockGallery = MOCK_PRODUCT.gallery;
    render(<ProductGallery gallery={mockGallery} productTitle="" />);
  });
  test('should render Product gallery for desktop', () => {
    expect(screen.getByTestId('product-gallery-container')).toBeVisible();
  });

  test('should render thumbnails on screen', () => {
    const thumbnails = screen.getAllByAltText('product thumbnail image');
    expect(thumbnails.length).toBe(4);
  });

  test('should render selected image on clicking on thumbnail', async () => {
    const thumbnails = screen.getAllByAltText('product thumbnail image');
    await userEvent.click(thumbnails[1].parentNode as HTMLElement);
    await waitFor(() => {
      expect(screen.getByAltText('Product image-1')).toBeVisible();
    });
  });
});

describe('<Thumbnail/>', () => {
  const mockGallery = MOCK_PRODUCT.gallery;
  const props = {
    thumbnails: mockGallery.map(({ thumbnail }) => thumbnail),
    onClick: jest.fn(),
  };
  test('should render thumbnails on screen', () => {
    render(<Thumbnails {...props} />);
    const thumbnails = screen.getAllByAltText('product thumbnail image');
    expect(thumbnails.length).toBe(4);
  });
  test('should fire callback on clicking on thumbnail', async () => {
    render(<Thumbnails {...props} />);
    const thumbnails = screen.getAllByAltText('product thumbnail image');
    await userEvent.click(thumbnails[1].parentNode as HTMLElement);
    expect(props.onClick).toBeCalled();
  });
});

describe('<ProductGalleryCarousel/>', () => {
  const mockGallery = MOCK_PRODUCT.gallery;
  test('should render images in the Carousel', () => {
    render(<ProductGalleryCarousel gallery={mockGallery} productTitle="" />);
    const productImages = screen.getAllByAltText('product image carousel');
    expect(productImages.length).toBe(4);
  });
});
