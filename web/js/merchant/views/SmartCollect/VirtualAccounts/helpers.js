/* eslint-disable consistent-return */
export const DESCRIPTOR_LENGTH_VPA = 20;
export const DESCRIPTOR_LENGTH_BANK_ACCOUNT = 16;
export const MERCHANT_PREFIX_MIN_LENGTH_VPA = 4;
export const MERCHANT_PREFIX_MAX_LENGTH_VPA = 10;

const CHAR_LENGTH = 7; // 7px length for 1px

export function getStyle_DescriptorInput_BankAccount(va_config) {
  if (!va_config || !va_config.hasOwnProperty('bank_account')) {
    return;
  }

  const styles = {
    paddingLeft: 28 + CHAR_LENGTH * va_config.bank_account.prefix.length,
  };

  return styles;
}

export function getStyle_AddOnBefore_BankAccount(va_config) {
  if (!va_config || !va_config.hasOwnProperty('bank_account')) {
    return;
  }

  const styles = {
    width: 20 + CHAR_LENGTH * va_config.bank_account.prefix.length,
    display: 'block',
    background: '#f1f3f4',
    height: '100%',
  };

  return styles;
}

export function getStyle_DescriptorInput_VPA(va_config) {
  if (!va_config || !va_config.hasOwnProperty('vpa')) {
    return;
  }

  const styles = {
    paddingLeft: 24 + CHAR_LENGTH * va_config.vpa.prefix.length, // 24 is to consider padding
    paddingRight: 28 + CHAR_LENGTH * va_config.vpa.handle.length,
  };

  return styles;
}

export function getStyle_AddOnBefore_VPA(va_config) {
  if (!va_config || !va_config.hasOwnProperty('vpa')) {
    return;
  }

  const styles = {
    width: 16 + CHAR_LENGTH * va_config.vpa.prefix.length, // 16 is to consider padding
    display: 'block',
    background: '#f1f3f4',
    height: '100%',
  };

  return styles;
}

export function getStyle_AddOnAfter_VPA(va_config) {
  if (!va_config || !va_config.hasOwnProperty('vpa')) {
    return;
  }

  const styles = {
    width: 24 + CHAR_LENGTH * va_config.vpa.handle.length, // 20 is to consider padding
    display: 'block',
    background: '#f1f3f4',
    height: '100%',
    marginLeft: -(CHAR_LENGTH * va_config.vpa.handle.length) + 12, // 12 is the default gap
  };

  return styles;
}

export function get_VPA_Handle(handle) {
  return `johnsmith@${handle}`;
}

export function getStyle_AddOnAfter_VPA_Prefix(handle) {
  if (!handle) return;

  const handleLength = handle.length;

  const styles = {
    width: 24 + CHAR_LENGTH * handleLength,
    display: 'block',
    background: '#f1f3f4',
    height: '100%',
    marginLeft: -(CHAR_LENGTH * handleLength) + 12,
  };

  return styles;
}

export function getStyle_AddOnBefore_VPA_Prefix(rzp_prefix) {
  if (!rzp_prefix) return;

  const styles = {
    width: 16 + CHAR_LENGTH * rzp_prefix.length,
    display: 'block',
    background: '#f1f3f4',
    height: '100%',
  };

  return styles;
}

export function getStyle_CustomPrefixInput_VPA(rzp_prefix, handle) {
  const styles = {
    paddingLeft: 24 + CHAR_LENGTH * rzp_prefix.length,
    paddingRight: 28 + CHAR_LENGTH * handle.length,
  };

  return styles;
}

export const isAxisBank = (bankAccount) => {
  if (!bankAccount) return false;
  return bankAccount.bank_name?.toLowerCase() === 'axis bank';
};
export const isRBLBank = (bankAccount) => {
  if (!bankAccount) return false;
  return bankAccount.bank_name?.toLowerCase() === 'rbl bank';
};
