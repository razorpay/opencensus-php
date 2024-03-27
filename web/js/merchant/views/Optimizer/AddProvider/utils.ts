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
  coverage: GatewayCoverage,
): boolean => {
  return mandatoryMethods.every((method) => coverage[method]?.supported);
};

export const getMethodCoverage = (methods: string[], data: GatewayCoverage): Coverage => {
  const coverage: Coverage = {};
  methods.forEach((method) => {
    coverage[method] = data[method]?.supported || false;
  });
  return coverage;
};
