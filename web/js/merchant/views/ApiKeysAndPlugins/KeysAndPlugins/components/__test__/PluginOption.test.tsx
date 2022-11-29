import React from 'react';
import { render } from 'test-utils';
import PluginOption from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/PluginOption';
import { SUPPORTED_PLUGINS } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/__test__/mocks/fixtures';

describe('API Keys & Plugins - Plugin Option', () => {
  test('should render option correctly', () => {
    const { getByText } = render(<PluginOption plugin={SUPPORTED_PLUGINS.Shopify} />);
    expect(getByText(SUPPORTED_PLUGINS.Shopify.name)).toBeInTheDocument();
  });

  test('should render option icon correctly', () => {
    const { getByRole } = render(<PluginOption plugin={SUPPORTED_PLUGINS.Wix} />);
    const iconURL = getByRole('img').getAttribute('src');
    expect(iconURL).toEqual(SUPPORTED_PLUGINS.Wix.icon);
  });
});
