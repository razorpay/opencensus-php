import React from 'react';

import DeviceStoreWrapper from 'apps/pos/src/app/views/SelfServe/DeviceStoreWrapper';
import { render, screen } from 'test-utils';

const initProps = {
  tabs: [
    {
      title: 'Test Tab',
      url: '/test-tab',
      isTabActive: true,
      isMatchStartsWith: true,
    },
    {
      title: 'Test Tab Two',
      url: '/test-tab-two',
      isTabActive: true,
      isMatchStartsWith: true,
    },
  ],
  extra: <div>Extra Item</div>,
  children: <div>Mock Children</div>,
  isTabsRequired: true,
  customHeaderRightClass: 'mock-class',
};

describe('<DeviceStoreWrapper/>', () => {
  test('should render DeviceStoreWrapper on screen', () => {
    render(<DeviceStoreWrapper {...initProps} />);
    expect(screen.getByText('Test Tab')).toBeVisible();
    expect(screen.getByText('Test Tab Two')).toBeVisible();

    expect(screen.getByText('Extra Item')).toBeVisible();
    expect(screen.getByText('Mock Children')).toBeVisible();
  });

  test('should render DeviceStoreWrapper with content only if tabs not required', () => {
    const newProps = {
      ...initProps,
      isTabsRequired: false,
    };
    render(<DeviceStoreWrapper {...newProps} />);
    expect(screen.queryByText('Test Tab')).toBeNull();

    expect(screen.getByText('Mock Children')).toBeVisible();
  });
});
