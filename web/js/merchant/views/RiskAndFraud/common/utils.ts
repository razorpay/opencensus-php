export const replaceBusinessName = (obj: {
  str: string;
  businessName: string;
  isCaps?: boolean;
}): string => {
  const { str, businessName, isCaps } = obj;
  try {
    if (!str) return '';
    // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
    let nameToReplace = businessName ? businessName.toLowerCase() : 'razorpay';
    if (isCaps) {
      nameToReplace = nameToReplace.charAt(0).toUpperCase() + nameToReplace.slice(1);
    }
    return str.replace('_businessName_', nameToReplace);
  } catch (error) {
    if (window.APP_ENV !== 'production')
      console.error('An error occurred while replacing business name:', error);
    return str;
  }
};
