import React from 'react';

import ExtraItems from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/SidebarGraphic/ExtraItems';
import SidebarGraphic from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/SidebarGraphic/SidebarGraphic';
import {
  AVAILABLE_GRAPHICS,
  SIDEBAR_DEFAULT_VALUE,
  SIDEBAR_GRAPHICS_ITEMS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';
import { render, screen, fireEvent } from 'test-utils';

jest.mock('merchant/views/Settings/Configuration/components/Configuration/FeatureToggle');
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
  CHECKOUT_FEATURE_FIELDS: {
    SIDEBAR_GRAPHIC: 'sidebarGraphic',
  },
}));

describe('SidebarGraphic', () => {
  const handleSidebarGraphicValueChange = jest.fn();
  test('renders SidebarGraphic with correct title and subtitle', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: {
          enabled: false,
          svg: AVAILABLE_GRAPHICS.NONE,
        },
      },
    });
    FeatureToggle.mockImplementation(() => <div>Feature Toggle</div>);
    render(<SidebarGraphic />);
    expect(FeatureToggle).toHaveBeenCalledWith(
      expect.objectContaining({
        title: SIDEBAR_DEFAULT_VALUE.title,
        subTitle: SIDEBAR_DEFAULT_VALUE.subTitle,
        extraItems: expect.anything(),
        feature: 'sidebarGraphic',
        isChecked: false,
      }),
      {},
    );
  });

  test('render the SidebarGraphic with enabled true', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: {
          enabled: true,
          svg: AVAILABLE_GRAPHICS.ECOM_SIDEBAR,
        },
      },
    });
    render(<ExtraItems />);
    SIDEBAR_GRAPHICS_ITEMS.forEach((item) => {
      expect(screen.getByTestId(`radio-${item.value}`)).toBeInTheDocument();
    });
  });

  test('render the SidebarGraphic with enabled true and selected graphic', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: {
          enabled: true,
          svg: AVAILABLE_GRAPHICS.ECOM_SIDEBAR,
        },
      },
    });
    render(<ExtraItems />);
    const selectedGraphic = screen.getByTestId(
      `test-sidebar-graphic-${SIDEBAR_GRAPHICS_ITEMS[2].value}`,
    );
    expect(selectedGraphic).toHaveAttribute(
      'aria-label',
      `${SIDEBAR_GRAPHICS_ITEMS[2].value}-selected`,
    );
  });

  it('calls handleSidebarGraphicValueChange when a graphics is clicked', () => {
    useCheckoutEditor.mockReturnValue({
      values: {
        [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: {
          enabled: true,
          svg: AVAILABLE_GRAPHICS.SIDEBAR,
        },
      },
      handleSidebarGraphicValueChange,
    });

    render(<ExtraItems />);

    const sidebar = screen.getByAltText(SIDEBAR_GRAPHICS_ITEMS[1].value).closest('div');
    fireEvent.click(sidebar);

    expect(handleSidebarGraphicValueChange).toHaveBeenCalledWith(AVAILABLE_GRAPHICS.SIDEBAR);
  });
});
