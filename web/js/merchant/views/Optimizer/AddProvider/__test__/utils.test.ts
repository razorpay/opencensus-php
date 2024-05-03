import { useSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';

import {
  isIntegrationAuditEnabled,
  isGatewaySupportIntegrationAudit,
  areMandatoryMethodsCovered,
  getMethodCoverage,
} from '../utils';

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };

const defaultAbExperiments = {};

let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments } as unknown as SpiltzContextState),
}));

describe('isIntegrationAuditEnabled', () => {
  beforeEach(() => {
    mockAbExperiments = defaultAbExperiments;
  });
  test('should return true for integration_audit', () => {
    mockAbExperiments = {
      integration_audit: variantOn,
    };
    const splitz = useSplitzService();
    expect(isIntegrationAuditEnabled(splitz)).toBe(true);
  });

  test('should return false for integration_audit', () => {
    mockAbExperiments = {
      integration_audit: variantOff,
    };
    const splitz = useSplitzService();
    expect(isIntegrationAuditEnabled(splitz)).toBe(false);
  });
});

describe('isGatewaySupportIntegrationAudit', () => {
  test('should return true for payu', () => {
    expect(isGatewaySupportIntegrationAudit('payu')).toBe(true);
  });

  test('should return false for ccavenue', () => {
    expect(isGatewaySupportIntegrationAudit('ccavenue')).toBe(false);
  });
});

describe('areMandatoryMethodsCovered', () => {
  test('should return true if mandatory methods are covered', () => {
    const mandatoryMethods = ['upi'];
    const coverage = [
      {
        method: 'upi',
        enabled: true,
      },
      {
        method: 'card',
        enabled: true,
      },
    ];
    expect(areMandatoryMethodsCovered(mandatoryMethods, coverage)).toBe(true);
  });

  test('should return false if mandatory methods are not covered', () => {
    const mandatoryMethods = ['upi'];
    const coverage = [
      {
        method: 'upi',
        enabled: false,
      },
      {
        method: 'card',
        enabled: true,
      },
    ];
    expect(areMandatoryMethodsCovered(mandatoryMethods, coverage)).toBe(false);
  });
});

describe('getMethodCoverage', () => {
  test('should return method coverage', () => {
    const data = [
      {
        method: 'upi',
        enabled: false,
      },
      {
        method: 'card',
        enabled: true,
      },
    ];
    expect(getMethodCoverage(data)).toEqual({
      card: true,
      upi: false,
    });
  });
});
