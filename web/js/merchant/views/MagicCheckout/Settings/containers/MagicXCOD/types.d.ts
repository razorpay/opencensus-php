import { ShippingMethod } from 'merchant/reducers/magicCheckout/shippingEngine/types';

export type FormErrors = {
  paymentMethod?: string;
  codSlabs?: string;
};

export type FormComponentProps = {
  formData: Record<string, unknown>;
  updateFormData: (key: string, vaue: unknown) => void;
  formErrors: FormErrors;
};

export type Amount = {
  gte: number;
  lt: number;
};

export type FormData = {
  fee_rules: { amount: Amount };
  cod_fee_rules: { amount: Amount | null };
  name: string;
  allow_cod: boolean;
  allow_prepaid: boolean;
  fee: number;
  [key: string]: any;
};

export interface FormContextType {
  formData: FormData;
  initialiseFormData: (value: any) => void;
  updateFormData: (key: string, value: any) => void;
  resetForm: () => void;
  formErrors: FormErrors;
}

export type ToggleCODPayload = {
  platform: string;
  shop_id: string;
  rcod: {
    enabled: boolean;
    configs?: object;
  };
};

type RedundantAttributes = {
  created_at: string;
  updated_at: string;
  shippingZone: string;
  profileName: string;
};

export type ShippingMethodPayload = Omit<ShippingMethod, RedundantAttribute> & {
  app_type: string;
  cod_fee_rules: { Amount };
};

export type Column = { title: string; tooltip: string; value: (item) => JSX.Element };
