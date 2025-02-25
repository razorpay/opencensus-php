import { isBlank } from "./isBlank";

/**
 * Generates a display name for a customer based on available name, contact, and email.
 * If multiple fields are provided, they are concatenated with ' / ' and formatted accordingly.
 * 
 * @param {Object} customer - The customer details.
 * @param {string} customer.name - The customer's name.
 * @param {string} customer.contact - The customer's contact information (e.g., phone number).
 * @param {string} customer.email - The customer's email address.
 * @returns {string} - The formatted customer display name.
 * 
 * @example
 * const displayName = getCustomerDisplayName({ name: 'John Doe', contact: '1234567890', email: 'john@example.com' });
 * console.log(displayName); // Output: "John Doe / 1234567890 / john@example.com"
 */
export const getCustomerDisplayName = ({
  name,
  contact,
  email,
}: {
  name?: string;
  contact?: string;
  email?: string;
}): string => {
  const displayParts = [name, contact, email].filter((item) => !isBlank(item));

  return `${displayParts.join(' / ').replace('/ ', '(')}${
    displayParts.length > 1 ? ')' : ''
  }`;
};
