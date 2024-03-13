// Added only Curlec, Razorpay as since these are the only recurring branding names that are frequently getting used. If required, we can add more to this list.
const orgNames = ['razorpay', 'curlec'];
function isBrandingHardCoded(value, node) {
  if (value.includes('@razorpay')) return false;

  const lowercaseValue = value.toLowerCase();

  const isOrgNameHardcoded = orgNames.some((orgName) => lowercaseValue.indexOf(orgName) !== -1);
  if (isOrgNameHardcoded) {
    return `Avoid using hardcoded "Razorpay or Curlec" for branding in your code, as branding of this feature might be different for each regions. Use the \`org.business_name\` to get the current org name. Please make sure the product owners for this feature, are aligned on using "Razorpay or Curlec" as branding.`;
  }
  return false;
}

module.exports = isBrandingHardCoded;
