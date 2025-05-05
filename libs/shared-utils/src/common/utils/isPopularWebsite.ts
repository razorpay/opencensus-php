/**
 * Checks if a URL belongs to a popular website.
 *
 * This function uses regular expressions to determine whether the input URL belongs
 * to a predefined list of popular websites. It also takes into account a list of
 * exclusions, which ensures that certain domains are excluded from the popular list.
 *
 * @param {string} inputUrl - The URL to be validated.
 * @returns {boolean} - Returns true if the input URL is part of popular websites and
 *                      does not match any exclusions; otherwise, it returns false.
 */

export const isPopularWebsite = (inputUrl: string | null | undefined): boolean => {
  if (!inputUrl) return false;

  const popularWebsites = [
    /\bgoogle\.com\b/i,
    /\bfacebook\.com\b/i,
    /\bfb\.com\b/i,
    /\binstagram\.com\b/i,
    /\byoutube\.com\b/i,
    /\btwitter\.com\b/i,
    /\bpaytm\.com\b/i,
    /\bphonepe\.com\b/i,
    /\bpay\.google\.com\b/i,
    /\bbharatpe\.com\b/i,
    /\brazorpay\.com\b/i,
    /\bexample\.com\b/i,
    /\blocalhost\.com\b/i,
    /\bamazon\.in\b/i,
    /\btest\.com\b/i,
    /\bsystem\.io\b/i,
    /\bindex\.html\b/i,
  ];

  const popularWebsitesExclusions = [/\bplay\.google\.com\b/i];

  // Check if URL matches any popular website pattern
  const isPopular = popularWebsites.some((regex) => regex.test(inputUrl));

  // Check if URL matches any exclusion pattern
  const isExcluded = popularWebsitesExclusions.some((regex) => regex.test(inputUrl));

  // Return true if it's a popular website and not excluded
  return isPopular && !isExcluded;
};
