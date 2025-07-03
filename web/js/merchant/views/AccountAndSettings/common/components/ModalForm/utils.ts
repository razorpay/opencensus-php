export const validateDisplayName = (displayName: string) => {
  const allowedCharacters = /^[a-zA-Z\s'-.]+$/.test(displayName);
  const BLOCKED_WORDS = [
    'LTD',
    'PVT',
    'LLC',
    'INC',
    'CORP',
    'LIMITED',
    'PRIVATE',
    'ENTERPRISES',
    'INDUSTRIES',
    'SERVICES',
    'TECHNOLOGIES',
    'COMPANY',
    'BUSINESS',
    'FIRM',
    'GROUP',
  ];

  if (displayName.length < 3)
    return { isValid: false, errorText: 'Display name should be at least 3 characters long.' };

  if (BLOCKED_WORDS.some((word) => displayName.toUpperCase().includes(word)))
    return {
      isValid: false,
      errorText: `Display name should not contain blocked words such as ${BLOCKED_WORDS.join(
        ', ',
      )}.`,
    };

  if (!allowedCharacters)
    return {
      isValid: false,
      errorText: 'Display name can only contain letters, spaces, hyphens, and apostrophes.',
    };

  return { isValid: true, errorText: '' };
};
