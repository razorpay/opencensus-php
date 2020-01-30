export const DESCRIPTOR_LENGTH_VPA = 20;
export const DESCRIPTOR_LENGTH_BANK_ACCOUNT = 16;

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
