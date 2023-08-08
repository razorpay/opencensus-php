import { useContext } from 'react';
import { SpiltzContext } from 'common/splitz/context/SplitzContextProvider';
import { SpiltzContextState } from 'common/splitz/types';

/**
 * A hook crafted for consuming dashboard's splitz service.
 */
export const useSplitzService = (): SpiltzContextState => useContext(SpiltzContext);
