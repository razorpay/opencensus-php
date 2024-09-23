import React from 'react';
import { render, screen } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import DashboardBannerWrapper from '../index';

describe('DashboardBanner Component', () => {
  test('Should not show the Banner, when user is not indian country', () => {
    const initialState = {
      growthService: {
        banners: [],
        loading: false,
      },
      session: {
        user: {
          isCountryIndia: false,
        },
      },
    };

    render(<DashboardBannerWrapper />, { initialState });
    expect(screen.getByTestId('component-wrapper')).toBeEmptyDOMElement();
  });
});
