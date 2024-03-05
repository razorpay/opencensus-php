import { useContext } from 'react';
import { SpiltzContext } from 'shell/SpiltzServiceContext';

import { SpiltzContextState } from 'common/splitz/types';

/**
 * A hook crafted for consuming dashboard's splitz service.
 */
export const useSplitzService = (): SpiltzContextState => useContext(SpiltzContext);
