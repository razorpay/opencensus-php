import fUnits from './field-units';

/*
* A. Type: text
*    Validation: single line text(string), number, email, phone, url, large text area
*
* B. Type: Select
*     Validation: string
*
* */

export const TYPES_not_now = [
  {
    label: 'Text',
    options: [
      fUnits.str,
      fUnits.number,
      fUnits.email,
      fUnits.phone,
      fUnits.url,
      fUnits.textarea,
    ],
  },
  fUnits.dropdown,
];

export const FIELD_TYPES = [
  fUnits.str,
  fUnits.number,
  fUnits.email,
  fUnits.phone,
  fUnits.url,
  fUnits.textarea,
];

// TODO: Check with Pronav/Amit regarding what if keys are added/deleted in future. In this case, score based matching could be better.
export function mapFieldToIndex(field) {
  let selectedIndexInOptions;

  // Removing the fixed schema fields
  const { title, name, required, description, ...schemaFields } = field;

  for (let i = 0; i < FIELD_TYPES.length; i++) {
    const FIELD_TYPES_keys = Object.keys(FIELD_TYPES[i].schema);
    const FIELD_TYPES_opts_keys = FIELD_TYPES[i].schema.options
      ? Object.keys(FIELD_TYPES[i].schema.options)
      : {};

    const field_keys = Object.keys(schemaFields);
    const field_opts_keys = schemaFields.options
      ? Object.keys(schemaFields.options)
      : {};

    if (
      FIELD_TYPES_keys.length !== field_keys.length ||
      FIELD_TYPES_opts_keys.length !== field_opts_keys.length
    ) {
      continue;
    }

    for (let j = 0; j < FIELD_TYPES_keys.length; j++) {
      if (FIELD_TYPES_keys[j] !== field_keys[j]) {
        break;
      }
    }

    for (let j = 0; j < FIELD_TYPES_opts_keys.length; j++) {
      if (FIELD_TYPES_opts_keys[j] !== field_opts_keys[j]) {
        break;
      }
    }

    selectedIndexInOptions = i;
    break;
  }

  if (!selectedIndexInOptions) {
    throw 'There is mismatch in Schema field.';
  }

  return selectedIndexInOptions;
}

export const FIELD_CONST = {
  get email() {
    return {
      name: 'email',
      required: true,
      title: 'Email',
      ...fUnits.email.schema,
    };
  },

  get phone() {
    return {
      name: 'phone',
      title: 'Phone',
      required: true,
      ...fUnits.phone.schema,
    };
  },
};
