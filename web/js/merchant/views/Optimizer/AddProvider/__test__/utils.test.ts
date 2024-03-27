import {
  isIntegrationAuditEnabled,
  isGatewaySupportIntegrationAudit,
  areMandatoryMethodsCovered,
  getMethodCoverage,
} from '../utils';
import { useSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';

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
    const coverage = {
      card: { supported: true },
      upi: { supported: true },
    };
    expect(areMandatoryMethodsCovered(mandatoryMethods, coverage)).toBe(true);
  });

  test('should return false if mandatory methods are not covered', () => {
    const mandatoryMethods = ['upi'];
    const coverage = {
      card: { supported: true },
      upi: { supported: false },
    };
    expect(areMandatoryMethodsCovered(mandatoryMethods, coverage)).toBe(false);
  });
});

describe('getMethodCoverage', () => {
  test('should return method coverage', () => {
    const methods = ['card', 'upi', 'netbanking'];
    const data = {
      card: { supported: true },
      upi: { supported: false },
      netbanking: { supported: true },
    };
    expect(getMethodCoverage(methods, data)).toEqual({
      card: true,
      upi: false,
      netbanking: true,
    });
  });
});
