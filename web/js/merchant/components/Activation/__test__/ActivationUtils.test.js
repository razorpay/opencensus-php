import { isEligibleForFtux } from '../ActivationUtils';

describe('isEligibleForFtux', () => {
  it('returns true when user is belongs to isINCountry is true and isOrgRZP is true', () => {
    const user = {
      isOrgRZP: true,
      isINCountry: true,
      isFtuxEnabled: true,
      activation_form_milestone: 'L2',
      activation_status: 'active',
      isPartner: () => false,
    };
    const abExperiments = {};
    expect(isEligibleForFtux({ user, abExperiments })).toBe(true);
  });

  it('returns false when user is belongs to isINCountry is false and isOrgRZP is true', () => {
    const user = {
      isOrgRZP: true,
      isINCountry: false,
      isFtuxEnabled: true,
      activation_form_milestone: 'L2',
      activation_status: 'active',
      isPartner: () => false,
    };
    const abExperiments = {};
    expect(isEligibleForFtux({ user, abExperiments })).toBe(false);
  });
});
