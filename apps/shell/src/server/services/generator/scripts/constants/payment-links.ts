const TEST_MID = '10000000000000';
const BHARTI_AXA = 'D2BsrUJVg04abr';

enum BhartiAxaProductOptions {
  SELECT = '',
  CAR = 'Car',
  HEALTH = 'Health',
  TRAVEL = 'Travel',
}

interface FormFieldOption {
  label: string;
  value: BhartiAxaProductOptions | string;
}

interface AddAt {
  fieldName: string;
  as: string;
}

interface ExtraFormField {
  name: string;
  label: string;
  fieldType: string;
  required: boolean;
  options: FormFieldOption[];
  addAt: AddAt;
}

interface CustomizedField {
  label: string;
  placeholder: string;
}

export const PL_DEFAULT_EXPIRY_IN_HOURS: Record<string, number> = {
  [BHARTI_AXA]: 72,
};

export const PL_EXTRA_FORM_FIELDS: Record<string, ExtraFormField[]> = {
  [BHARTI_AXA]: [
    {
      name: 'extra_field_1', // Should be unique
      label: 'Product',
      fieldType: 'Select',
      required: true,
      options: [
        { label: '--Select--', value: BhartiAxaProductOptions.SELECT },
        { label: 'Car', value: BhartiAxaProductOptions.CAR },
        { label: 'Health', value: BhartiAxaProductOptions.HEALTH },
        { label: 'Travel', value: BhartiAxaProductOptions.TRAVEL },
      ],
      addAt: {
        fieldName: 'description',
        as: 'prefix',
      },
    },
  ],
};

export const PL_DEFAULT_CUSTOMIZED_FIELDS: Record<string, CustomizedField> = {
  receipt: {
    label: 'Receipt No.',
    placeholder: '',
  },
  description: {
    label: 'Payment For',
    placeholder: 'Payment Description',
  },
};

export const PL_CUSTOMIZED_FIELDS: Record<string, Record<string, CustomizedField>> = {
  [BHARTI_AXA]: {
    receipt: {
      label: 'Reference Number',
      placeholder: '',
    },
    description: {
      label: 'Policy/Vehicle Registration Number',
      placeholder: '',
    },
  },
};

export const PL_ENABLE_CUSTOMER_NAME_FIELD: Record<string, boolean> = {
  [BHARTI_AXA]: true,
};
