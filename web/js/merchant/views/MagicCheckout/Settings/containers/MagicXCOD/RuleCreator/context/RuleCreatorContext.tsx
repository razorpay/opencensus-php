import { createContext } from 'react';

import { RuleCreatorContextType } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const RuleCreatorContext = createContext<RuleCreatorContextType>({});
