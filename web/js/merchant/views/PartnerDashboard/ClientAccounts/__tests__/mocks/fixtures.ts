// Required for announcements:
const instantActivation = { isWhitelistFlow: false };
export const defaultUserExtra = {
  id: 'testUserId',
  userRole: 'owner',
  isAuthenticated: true,
  isOrgRZP: true,
  isPartnershipForCapitalEnabled: true,
  isPartnershipFUX: true,
  instantActivation,
  isPartnerAgentRole: false,
  isOrgAllowedFunctionality: () => true,
};
