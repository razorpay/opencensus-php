import axios from 'axios';

/**
 * Fetches the IFSC bank details for the provided IFSC code.
 *
 * @param {string} ifscCode - The IFSC code to fetch details for.
 * @returns {Promise<{ Bank: string, Branch: string, City: string, State: string } | null>} 
 * A promise that resolves to an object containing the bank details (Bank, Branch, City, State) or null if invalid.
 *
 * @example
 * getDetailsForIFSC('HDFC0001234')
 *   .then(details => console.log(details))
 *   .catch(error => console.error(error));
 */
export function getDetailsForIFSC(ifscCode: string): Promise<{
  Bank: string;
  Branch: string;
  City: string;
  State: string;
} | null> {
  const IFSCCodeValidatorRegex = /^[A-Z]{4}0[A-Z0-9]{6}$/i;
  
  if (ifscCode.length !== 11 || !IFSCCodeValidatorRegex.test(ifscCode)) {
    return Promise.resolve(null);
  }

  return axios(`https://ifsc.razorpay.com/${ifscCode}`).then((info) => {
    const data = info.data;

    if (data) {
      return {
        Bank: data.BANK,
        Branch: data.BRANCH,
        City: data.CITY,
        State: data.STATE,
      };
    }

    return null; // Invalid IFSC code
  });
}
