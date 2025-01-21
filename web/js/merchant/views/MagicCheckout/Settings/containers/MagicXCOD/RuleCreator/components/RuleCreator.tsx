import * as React from 'react';

import { RCEngineProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/RuleCreatorContext';

import type { RCEngineProps } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const RuleCreatorEngine: React.FC<RCEngineProps> = (props) => {
  const { children, ...restProps } = props;

  return <RCEngineProvider {...restProps}>{children}</RCEngineProvider>;
};
