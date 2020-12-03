export const PROPRIETORSHIP = 1;
export const PARTNERSHIP = 3;
export const PRIVATE = 4; // 'Private Limited',
export const PUBLIC = 5; // 'Public Limited',
export const LLP = 6; // 'LLP'
export const NGO = 7; // 'NGO'
export const TRUST = 9; // 'Trust'
export const SOCIETY = 10; // 'Society'
export const NOT_REGISTERED = 11; // 'Unregistered Businesses

export const CIN_BusinessTypes = [PRIVATE, PUBLIC];
export const LLPIN_BusinessTypes = [LLP];
export const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

const UNREGISTERED_TYPES = {
  11: true,
  2: true,
};

export function isUnregisteredBusiness(businessType): boolean {
  return !!UNREGISTERED_TYPES[Number(businessType)];
}
