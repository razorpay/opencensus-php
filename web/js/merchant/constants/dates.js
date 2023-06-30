// This is to address a product requirement to enable a feature by default
// for new partner accounts that get created after the feature launch date.
// This workaround is needed because we don't support calling experiment with additional
// params(bulk call happens at dashboard BE)
export const PARTNERSHIPS_INVITES_TAB_AUDIENCE_EPOCH = 1688083200; // 30 June 2023
