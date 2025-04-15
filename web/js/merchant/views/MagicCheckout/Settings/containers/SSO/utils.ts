import { LoginScreenOption } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';

// returns an array of LoginScreenOption objects
export function constructPayloadObjects(
  checkBoxkeys: string[],
  dataObject: Record<string, string | number | null>,
  isMandatory: boolean = false,
): LoginScreenOption[] {
  const keysArray = checkBoxkeys.filter((key) => key !== 'checkout_init_mandatory_login');  
  return keysArray.map((key) => ({
    type: key,
    delay: key in dataObject ? Number(dataObject[key]) : key === 'checkout_init' ? null : 3,
    mandatory: key === 'checkout_init' ? isMandatory : false,
  })) as LoginScreenOption[];
}
