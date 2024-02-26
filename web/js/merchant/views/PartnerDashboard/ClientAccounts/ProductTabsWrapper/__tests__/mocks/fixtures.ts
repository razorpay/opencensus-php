// Mock required for announcements -
const instantActivation = { isWhitelistFlow: false };
export const staticUserExtra = {
  id: 'testUserId',
  // For ShowWhen to work -
  userRole: 'owner',
  isAuthenticated: true,
  // Needed for BatchValidate checks:
  isOrgAllowedFunctionality: () => true,
  isOrgRZP: true,
  isPartnershipForCapitalEnabled: true,
  isPartnershipFUX: true,
  instantActivation,
  isPartnerAgentRole: false,
};
export const staticLocation = {
  key: '',
  hash: '',
  search: '',
  state: {},
  pathname: '/partners/submerchants',
};
