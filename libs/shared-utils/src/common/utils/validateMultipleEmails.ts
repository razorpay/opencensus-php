import { isEmail } from "./isEmail";

/**
 * Validates an array of email addresses to ensure all are valid.
 * 
 * @param {string[]} emails - An array of email addresses to validate.
 * @returns {boolean} - Returns true if all email addresses are valid, false otherwise.
 * 
 * @example
 * const validEmails = validateMultipleEmails(['test@example.com', 'user@domain.com']);
 * // returns true
 * 
 * const invalidEmails = validateMultipleEmails(['test@example.com', 'invalid-email']);
 * // returns false
 */
export function validateMultipleEmails(emails: string[] | null | undefined): boolean {
  if (!emails || emails.length === 0) {
    return false; // Return false for empty or null email lists
  }

  return emails.every((email) => !!email && isEmail(email));
}
