import type { FormikErrors, FormikTouched, FormikHandlers } from 'formik';

export type CountryReturnType = {
  name: string;
  code: string;
  dial_code: string;
}[];

export type StateReturnType = {
  value: string;
  label: string;
}[];

export type BuyerAddressType = {
  [x: string]: string;
};

export type BuyerAddressModalProps = {
  paymentId: string;
  showNotification: (arg: { type: string; message: string }) => void;
  onClose: () => void;
};

export type UseBuyerAddressStateTypes = {
  isStatesLoading: boolean;
  isAddressLoading: boolean;
  isAddressSaving: boolean;
  states: StateReturnType;
  countries: CountryReturnType;
  buyerAddress: BuyerAddressType;
  isAddressAlreadyExists: boolean;
  onCountrySelect: (code: string) => void;
  onSaveAddress: (values: { [x: string]: string }) => void;
};

export type BuyerAddressFormProps = Pick<
  UseBuyerAddressStateTypes,
  | 'states'
  | 'countries'
  | 'isAddressLoading'
  | 'isStatesLoading'
  | 'isAddressAlreadyExists'
  | 'onCountrySelect'
> & {
  errors: FormikErrors<BuyerAddressType>;
  values: BuyerAddressType;
  touched: FormikTouched<BuyerAddressType>;
  onBlur: FormikHandlers['handleBlur'];
  onChange: FormikHandlers['handleChange'];
};
