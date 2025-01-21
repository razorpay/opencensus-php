import { useMemo } from 'react';

import { getFactsFromProps } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/utils';

import type { UseFactsInternal } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const useFactsInternal: UseFactsInternal = (props) => {
  const facts = useMemo(() => getFactsFromProps(props), [props.facts]);

  return facts;
};
