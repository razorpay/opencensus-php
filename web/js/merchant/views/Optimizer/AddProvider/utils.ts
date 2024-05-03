import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { INTEGRATION_AUDIT_COVERED_GATEWAY } from 'merchant/views/Navigator/constants';

import { GatewayCoverage, Coverage } from './types';

export const isIntegrationAuditEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { integration_audit: undefined } };
  if (!abExperiments?.integration_audit) return false;
  return isExperimentEnabled(abExperiments.integration_audit);
};

export const isGatewaySupportIntegrationAudit = (gateway: string): boolean => {
  return INTEGRATION_AUDIT_COVERED_GATEWAY.includes(gateway);
};

export const areMandatoryMethodsCovered = (
  mandatoryMethods: string[],
  coverage: GatewayCoverage[],
): boolean => {
  let checked = 0;
  let isMethodCovered = true;
  for (const item of coverage) {
    if (checked === mandatoryMethods.length) {
      break;
    }
    if (mandatoryMethods.includes(item.method)) {
      isMethodCovered = isMethodCovered && !!item.enabled;
      if (!isMethodCovered) {
        break;
      }
      checked++;
    }
  }
  return isMethodCovered;
};

/**
 * Transform coverage data to a map of method and its coverage status
 * @param data coverage data of gateway methods
 * @returns methods with coverage status
 */
export const getMethodCoverage = (data: GatewayCoverage[]): Coverage => {
  const coverage: Coverage = {};
  data.forEach((item) => {
    coverage[item.method] = item.enabled || false;
  });
  return coverage;
};
