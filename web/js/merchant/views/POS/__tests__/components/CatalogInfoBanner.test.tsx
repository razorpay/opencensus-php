import React from 'react';

import CatalogInfoBanner from 'merchant/views/POS/Catalog/CatalogInfoBanner';
import { screen, render } from 'test-utils';
import { ScrollObserverProvider } from 'merchant/views/POS/utils/ScrollObserver';
import { setupIntersectionObserverMock } from 'merchant/views/POS/utils/IntersectionObserverMock';

describe('<CatalogInfoBanner/>', () => {
  beforeEach(() => {
    setupIntersectionObserverMock();
  });

  test('should render main banner on screen', () => {
    render(
      <ScrollObserverProvider>
        <CatalogInfoBanner />
      </ScrollObserverProvider>,
    );
    expect(screen.getByText('Your POS is Just a Few Clicks Away!')).toBeVisible();
    expect(
      screen.getByText(
        'Easily order your POS devices online with just a few clicks, and receive them within 2-3 days after KYC approval.',
      ),
    ).toBeVisible();
  });

  test('should render timeline component on screen', () => {
    render(
      <ScrollObserverProvider>
        <CatalogInfoBanner />
      </ScrollObserverProvider>,
    );
    expect(screen.getByTestId('banner-timeline')).toBeVisible();
    expect(screen.getByText('Select your favourite device.')).toBeVisible();
    expect(screen.getByText('Place an online order.')).toBeVisible();
    expect(
      screen.getByText(
        'Sit tight while we bring your device to you in 2-3 days post KYC approval.',
      ),
    ).toBeVisible();
  });
});
