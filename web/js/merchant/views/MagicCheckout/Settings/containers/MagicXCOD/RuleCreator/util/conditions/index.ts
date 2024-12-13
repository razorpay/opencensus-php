import { isPojo } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/misc';

import type { ConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const isConditionGroup = (obj: any): obj is ConditionGroup => {
  return isPojo(obj) && Array.isArray(obj.conditions);
};
