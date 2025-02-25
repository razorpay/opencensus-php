/**
 * Checks the validity of an address.
 * Line1, City, State, Country, and Zipcode are required fields in an address.
 * @param {Object} address - The address object containing required and optional fields.
 * @return {boolean} - Returns true if the address is valid, false otherwise.
 */
interface Address {
  line1: string;
  line2?: string;
  city: string;
  state: string;
  country: string;
  zipcode: string;
}

export const isAddressValid = (address: Address): boolean => {
  const allKeys = Boolean(
    address &&
    address.line1 &&
    address.city &&
    address.state &&
    address.country &&
    address.zipcode
  );

  if (!allKeys) {
    return false;
  }

  const { line1, line2, city, state, country } = address;

  const requiredFieldsLengthCheck = Boolean(
    line1.length >= 10 &&
      line1.length <= 255 &&
      city.length >= 2 &&
      city.length <= 32 &&
      state.length >= 2 &&
      state.length <= 32 &&
      country.length >= 2 &&
      country.length <= 64
  );

  let optionalFieldsLengthCheck = true;
  if (line2 && !(line2.length >= 5 && line2.length <= 255)) {
    optionalFieldsLengthCheck = false;
  }

  const lengthCheck = requiredFieldsLengthCheck && optionalFieldsLengthCheck;

  return lengthCheck;
};
