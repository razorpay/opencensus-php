export function setClarityTag(key, value) {
  try {
    window.clarity?.('set', key, value);
  } catch (e) {}
}

export function setCommonAttributesForClarity(user) {
  try {
    const {
      role,
      current: merchantId,
      activation_status: activationStatus,
      verification: { status: verificationStatus } = {},
      user: { id: userId } = {},
      partner: { partner_type: partnerType } = {},
      merchant: { country_code } = {},
    } = user || {};

    // Merchant related
    setClarityTag('activationStatus', activationStatus ?? null);
    setClarityTag('verificationStatus', verificationStatus ?? null);
    setClarityTag('merchantId', merchantId ?? null);
    setClarityTag('country_code', country_code ?? null);

    // User related
    setClarityTag('role', role ?? null);
    setClarityTag('userId', userId ?? null);
    
    // Partner related
    setClarityTag('partnerType', partnerType ?? null);
  } catch (e) {}
}