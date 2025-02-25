import { useContext } from 'react';
import {SpiltzContext} from "@federated/dashboards/payments/services/splitzService";

import { SpiltzContextState } from 'common/splitz/types';

/**
 * A hook crafted for consuming dashboard's splitz service.
 */
export const useSplitzService = (): SpiltzContextState => useContext(SpiltzContext);
