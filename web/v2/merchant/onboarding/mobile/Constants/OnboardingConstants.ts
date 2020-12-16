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

export const BusinessTypes = {
  [PROPRIETORSHIP]: 'Proprietorship',
  [PARTNERSHIP]: 'Partnership',
  [PRIVATE]: 'Private Limited',
  [PUBLIC]: 'Public Limited',
  [LLP]: 'LLP',
  [NGO]: 'NGO',
  [TRUST]: 'Trust',
  [SOCIETY]: 'Society',
  [NOT_REGISTERED]: 'Not Registered',
};

export function isUnregisteredBusiness(businessType): boolean {
  return !!UNREGISTERED_TYPES[Number(businessType)];
}

export const states = {
  AN: 'Andaman And Nicobar',
  AP: 'Andhra Pradesh',
  AR: 'Arunachal Pradesh',
  AS: 'Assam',
  BI: 'Bihar',
  CH: 'Chandigarh (UT)',
  CT: 'Chattisgarh',
  DN: 'Dadra And Nagar Haveli',
  DD: 'Daman And Diu (UT)',
  DL: 'Delhi',
  GO: 'Goa',
  GJ: 'Gujarat',
  HA: 'Haryana',
  HP: 'Himachal Pradesh',
  JK: 'Jammu And Kashmir',
  JH: 'Jharkhand',
  KA: 'Karnataka',
  KE: 'Kerala',
  LD: 'Lakshadweep',
  MP: 'Madhya Pradesh',
  MH: 'Maharashtra',
  MA: 'Manipur',
  ME: 'Meghalaya',
  MI: 'Mizoram',
  NA: 'Nagaland',
  OR: 'Orissa',
  PO: 'Pondicherry(UT)',
  PB: 'Punjab',
  RJ: 'Rajasthan',
  SK: 'Sikkim',
  TG: 'Telangana',
  TN: 'Tamilnadu',
  TR: 'Tripura',
  UP: 'Uttar Pradesh',
  UT: 'Uttranchal',
  WB: 'West Bengal',
};
