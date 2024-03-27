export const getDocLink = (link: string, businessName: string): string => {
  if (!link) return '';

  const nameToReplace = businessName ? businessName.toLowerCase() : 'razorpay';
  return link.replace('_domain_', nameToReplace);
};
