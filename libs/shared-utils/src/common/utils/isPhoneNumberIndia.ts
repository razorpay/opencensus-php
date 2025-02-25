export const isPhoneNumberIndia = (phone: string) => {
    phone = phone || '';
    const phoneRegExp = new RegExp(/^\+?[0-9]{10}$/);
    return phoneRegExp.test(phone);
  };