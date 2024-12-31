const makePhoneNumber = (phone: { countryCode: string | null; number: string }): string => {
  return phone?.countryCode ? `${phone?.countryCode + phone?.number}` : phone?.number;
};

export default makePhoneNumber;
