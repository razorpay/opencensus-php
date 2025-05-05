import { validatePANCard } from './validatePANCard';

/**
 * Enum of supported normalized business types.
 * These values are mapped to the 4th character of the PAN card, which indicates the type of entity.
 */

type BusinessType =
  | 'PRIVATE'
  | 'PUBLIC'
  | 'LLP'
  | 'PARTNERSHIP'
  | 'TRUST'
  | 'SOCIETY'
  | 'HUF'
  | 'INDIVIDUAL';

/**
 * Mapping of business types to the valid 4th character(s) allowed in a PAN number.
 * The 4th character in a PAN reflects the type of entity (e.g., 'C' for Company, 'P' for Individual).
 */

const BUSINESS_TYPE_PAN_LETTERS: Record<BusinessType, string[]> = {
  PRIVATE: ['C', 'G'],
  PUBLIC: ['C', 'G'],
  LLP: ['F', 'G'],
  PARTNERSHIP: ['F', 'G'],
  TRUST: ['A', 'B', 'T', 'G'],
  SOCIETY: ['A', 'B', 'T', 'G', 'L'],
  HUF: ['H', 'G'],
  INDIVIDUAL: ['P'],
};

const getErrorLabel = (type: string): string => {
  switch (type) {
    case 'Private':
      return 'Private Limited';
    case 'Public':
      return 'Public Limited';
    default:
      return type;
  }
};

/**
 * Validates a given PAN number for correctness and checks that the 4th character
 * aligns with the expected pattern based on the provided business type.
 *
 * Use Case:
 * - During onboarding/KYC flows where businesses must submit valid PAN details.
 * - Ensures PAN matches the format and entity classification rules.
 *
 * @param value The PAN number to validate (should be 10 characters).
 * @param businessType Raw business type string (e.g., 'Private', 'LLP') from BUSINESS_TYPE_MAP.
 * @returns A user-friendly error message if invalid, or undefined if valid.
 */

export function validateCompanyPanWithBusinessType(
  value: string | null | undefined,
  businessType: string,
): string | undefined {
  if (!value) return undefined;

  const panValidationError = validatePANCard(value);
  if (typeof panValidationError === 'string') return panValidationError;

  if (businessType) {
    const normalizedBusinessType = businessType.toUpperCase() as BusinessType;

    if (!Object.keys(BUSINESS_TYPE_PAN_LETTERS).includes(normalizedBusinessType)) {
      return `Invalid business type: ${businessType}`;
    }

    const allowedLetters = BUSINESS_TYPE_PAN_LETTERS[normalizedBusinessType];
    if (!allowedLetters.includes(value[3].toUpperCase())) {
      return `Invalid PAN format for ${getErrorLabel(businessType)}`;
    }
  }

  return undefined;
}
