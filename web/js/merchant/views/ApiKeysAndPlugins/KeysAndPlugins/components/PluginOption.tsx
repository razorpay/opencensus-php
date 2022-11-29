import React from 'react';
import { Plugin } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';

const PluginOption = ({
  plugin,
  selected,
}: {
  plugin: Plugin;
  selected?: boolean;
}): JSX.Element => {
  return (
    <div className="plugin-select__option">
      <img
        className="plugin-select__icon"
        src={plugin?.icon}
        data-testid={`plugin-${selected ? 'selected' : 'option'}-${plugin?.name}`}
      />
      <span>{plugin?.name}</span>
    </div>
  );
};

export default PluginOption;
