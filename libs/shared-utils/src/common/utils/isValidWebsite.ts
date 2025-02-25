interface WebsiteValidationOptions {
    url?: string;
    isRazorpayDomainAllowed?: boolean;
    allowHttpProtocol?: boolean;
  }
  
  /**
   * Validates whether a given URL is valid based on the provided options.
   * 
   * This function checks if the URL is valid according to the following criteria:
   * 1. It can enforce only HTTPS protocol (or allow HTTP if specified).
   * 2. It can block URLs containing "razorpay" unless the user has an email that ends with "razorpay.com".
   * 
   * @param {WebsiteValidationOptions} options - The options for validating the website URL.
   * @param {string} [options.url=''] - The URL to validate.
   * @param {boolean} [options.isRazorpayDomainAllowed=false] - Allows "razorpay" in the URL if set to true.
   * @param {boolean} [options.allowHttpProtocol=false] - Allows HTTP protocol in the URL if set to true.
   * @returns {boolean} Returns `true` if the URL is valid, `false` otherwise.
   * 
   * @example
   * // Example 1: Valid HTTPS URL
   * const result = isValidWebsite({ url: 'https://example.com' });
   * console.log(result); // Output: true
   * 
   * @example
   * // Example 2: Valid URL allowing HTTP
   * const result = isValidWebsite({ url: 'http://example.com', allowHttpProtocol: true });
   * console.log(result); // Output: true
   * 
   * @example
   * // Example 3: Invalid Razorpay domain for non-razorpay email
   * const result = isValidWebsite({ url: 'https://test-razorpay.com' });
   * console.log(result); // Output: false (for non-razorpay emails)
   * 
   * @example
   * // Example 4: Valid Razorpay domain for razorpay email
   * window.rzp_user = { email: 'test@razorpay.com' };
   * const result = isValidWebsite({ url: 'https://test-razorpay.com', isRazorpayDomainAllowed: true });
   * console.log(result); // Output: true
   */
  export const isValidWebsite = ({
    url = '',
    isRazorpayDomainAllowed = false,
    allowHttpProtocol = false,
  }: WebsiteValidationOptions): boolean => {
    // Regular expression to validate only HTTPS URLs
    const onlyHttpsUrlRegExp =
      /^(https:\/\/)?(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}(?:\/[\w\-._~:/?#[\]@!$&'()*+,;=]*)?$/;
  
    // Regular expression to validate both HTTP and HTTPS URLs
    const allowHttpUrlRegExp =
      /^(https?:\/\/)?(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}(?:\/[\w\-._~:/?#[\]@!$&'()*+,;=]*)?$/;
  
    // Use appropriate regex based on whether HTTP is allowed
    const urlRegExp = allowHttpProtocol ? allowHttpUrlRegExp : onlyHttpsUrlRegExp;
  
    /**
     * Check for Razorpay-specific URL validation.
     * Non-Razorpay users (non @razorpay.com emails) should not be able to add URLs containing "razorpay".
     */
    if (
      !isRazorpayDomainAllowed &&
      url.includes('razorpay') &&
      !window.rzp_user?.email?.endsWith('razorpay.com')
    ) {
      return false;
    }
  
    // Validate the URL against the appropriate regular expression
    return urlRegExp.test(url);
  };
  