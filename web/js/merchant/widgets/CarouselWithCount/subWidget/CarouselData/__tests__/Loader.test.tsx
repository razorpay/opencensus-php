import React from 'react';

import { render, screen } from 'test-utils';
import { CarouselDataWidgetLoader } from '../Loader';

describe('Widgets->CarouselWithCount->Loader', () => {
  test('should render loader', () => {
    render(<CarouselDataWidgetLoader />);
    expect(screen.getByTestId('data-widget-loader')).toBeVisible();
  });
});
