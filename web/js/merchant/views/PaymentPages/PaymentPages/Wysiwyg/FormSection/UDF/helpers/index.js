import fUnits from './field-units';

/*
 * A. Type: text
 *    Validation: single line text(string), alphabets, alphanumeric, number, email, phone, url, large text area, pan, pincode
 *
 * B. Type: Select
 *     Validation: string
 *
 * */

export function getFieldTypes(isPaymentButton) {
  const isBatchPaymentPages = window.location.pathname.includes('/batchpaymentpages');
  let FIELD_TYPES = [
    fUnits.str,
    fUnits.alphabets,
    fUnits.alphanumeric,
    fUnits.number,
    fUnits.email,
    fUnits.phone,
    fUnits.url,
    fUnits.textarea,
    fUnits.pan,
    fUnits.pincode,
    fUnits.dropdown,
  ];

  if (!isPaymentButton) {
    FIELD_TYPES.push(fUnits.date);
  }

  if (isBatchPaymentPages) {
    const primaryRefIdField = fUnits.alphanumeric;
    primaryRefIdField.label = 'Primary Reference ID';
    FIELD_TYPES.unshift(primaryRefIdField);
    // remove dropdown
    FIELD_TYPES = FIELD_TYPES.filter((fUnits) => fUnits?.label !== 'Dropdown');
  }

  return FIELD_TYPES; // JSON.parse(JSON.stringify(FIELD_TYPES)) is best way. But need to check if it breaks the selection in powerselect dropdown bcoz it works on object reference basis
}

export function flattenFIELD_TYPES() {
  const flatten = [];
  const FIELD_TYPES = getFieldTypes();

  for (let i = 0; i < FIELD_TYPES.length; i++) {
    const FIELD = FIELD_TYPES[i];
    if (FIELD.options) {
      for (let j = 0; j < FIELD.options.length; j++) {
        const SUB_FIELD = FIELD.options[j];
        SUB_FIELD.level = [i, j];
        flatten.push(SUB_FIELD);
      }
    } else {
      FIELD.level = [i];
      flatten.push(FIELD);
    }
  }

  return flatten;
}

// Note: If schema for a given field is changed, then this fn. will break.
export function mapFieldToIndex(field) {
  let selectedIndexInOptions = null;

  // Removing the fixed schema fields
  const { title, name, required, description, settings, ...schemaFields } = field;

  const { options: optionsInFieldSchema, ...restInFieldSchema } = schemaFields;
  const fieldTypes = flattenFIELD_TYPES();

  for (let i = 0; i < fieldTypes.length; i++) {
    const { options: optionsInDefinedSchema, ...restInDefinedSchema } = fieldTypes[i].schema;

    const FIELD_TYPES_keys = Object.keys(restInDefinedSchema); //Needs to be separated since backend sometimes sends empty options when it's not required.
    const FIELD_TYPES_opts_keys = optionsInDefinedSchema ? Object.keys(optionsInDefinedSchema) : [];

    const field_keys = Object.keys(restInFieldSchema);
    const field_opts_keys = optionsInFieldSchema ? Object.keys(optionsInFieldSchema) : [];

    let isMismatch = false;

    if (
      FIELD_TYPES_keys.length !== field_keys.length ||
      FIELD_TYPES_opts_keys.length !== field_opts_keys.length
    ) {
      isMismatch = true;
    }

    if (isMismatch) {
      // eslint-disable-next-line no-continue
      continue;
    }

    for (let j = 0; j < FIELD_TYPES_keys.length; j++) {
      // EXCEPTION: value for enum is not to be compared as it's an array and will have unique values, it can be skipped and options.cmp will handle existence of 'key:enum'
      if (['enum'].indexOf(FIELD_TYPES_keys[j]) > -1) {
        // eslint-disable-next-line no-continue
        continue;
      }

      const valueInFieldSchema = schemaFields[FIELD_TYPES_keys[j]];
      const valueInFieldMapSchema = fieldTypes[i].schema[FIELD_TYPES_keys[j]];

      if (valueInFieldSchema !== valueInFieldMapSchema) {
        isMismatch = true;
        break;
      }
    }

    if (isMismatch) {
      // eslint-disable-next-line no-continue
      continue;
    }

    for (let j = 0; j < FIELD_TYPES_opts_keys.length; j++) {
      // EXCEPTION 1: values of schema.options.enum_label will always be different. So, skipped because relying on schema.options.cmp == 'select'
      if (['enum_labels'].indexOf(FIELD_TYPES_opts_keys[j]) > -1) {
        // eslint-disable-next-line no-continue
        continue;
      }

      const valueInFieldSchemaOptions =
        schemaFields.options && schemaFields.options[FIELD_TYPES_opts_keys[j]];
      const valueInFieldMapSchemaOptions =
        fieldTypes[i].schema.options && fieldTypes[i].schema.options[FIELD_TYPES_opts_keys[j]];

      if (valueInFieldSchemaOptions !== valueInFieldMapSchemaOptions) {
        isMismatch = true;
        break;
      }
    }

    if (isMismatch) {
      // eslint-disable-next-line no-continue
      continue;
    }

    selectedIndexInOptions = fieldTypes[i].level;
    break;
  }

  if (selectedIndexInOptions === null) {
    throw new Error('There is mismatch in Schema field.');
  }

  return selectedIndexInOptions;
}

export function getFieldFromIndices(indicesString) {
  let FIELD;
  const FIELD_TYPES = getFieldTypes();

  if (indicesString === null || indicesString === undefined) {
    return false;
  }

  indicesString = indicesString.split(' ');

  if (indicesString.length === 1) {
    FIELD = FIELD_TYPES[indicesString[0]];
  } else {
    FIELD = FIELD_TYPES[indicesString[0]];
    const sub_options = FIELD && FIELD.options;

    if (!sub_options) {
      return false;
    }
    FIELD = sub_options[indicesString[1]];
  }

  return FIELD && FIELD.schema;
}

export function constructFieldSchema(fieldData) {
  const { title, required, description, field_type } = fieldData;

  if (field_type === null || field_type === undefined) {
    return false;
  }

  const SCHEMA = getFieldFromIndices(String(field_type));

  if (!title || !SCHEMA) {
    return false;
  }

  const prettyTitle = title.trim().replace('  ', ' ');

  return {
    name: prettyTitle.trim().toLowerCase().split(' ').join('_'),
    title: prettyTitle,
    required: typeof required !== 'undefined' ? !!Number(required) : undefined,
    description: typeof description !== 'undefined' ? description : undefined,
    ...SCHEMA,
  };
}

// Check if keys have only supported keys in udf schema and non-duplicate keys
export function _areKeysSupported(keys) {
  const exhaustiveSet = [
    'type',
    'name',
    'title',
    'pattern',
    'description',
    'required',
    'minLength',
    'maxLength',
    'minimum',
    'maximum',
    'enum',
    'options',
    'settings',
  ];

  for (let k = 0; k < keys.length; k++) {
    if (exhaustiveSet.indexOf(keys[k]) === -1 || keys.indexOf(keys[k]) !== k) {
      return false;
    }
  }

  return true;
}

// Check if keys have only supported keys in udf schema and non-duplicate keys
export function _areOptionsKeysSupported(keys) {
  const exhaustiveSet = ['cmp', 'keydown_restrictive', 'enum_labels'];

  for (let k = 0; k < keys.length; k++) {
    if (exhaustiveSet.indexOf(keys[k]) === -1 || keys.indexOf(keys[k]) !== k) {
      return false;
    }
  }

  return true;
}

export function _isSupportedType(type) {
  const supportedTypes = ['string', 'number'];

  return supportedTypes.indexOf(type) > -1;
}

export function _isSupportedPattern(pattern) {
  const supportedPatterns = [
    'email',
    'phone',
    'number',
    'url',
    'alphanumeric',
    'alphabets',
    'pan',
    'date',
  ];

  return supportedPatterns.indexOf(pattern) > -1;
}

export function _isSupportedComponent(cmp) {
  const supportedCmp = ['select', 'textarea', 'input', 'date'];

  return supportedCmp.indexOf(cmp) > -1; // Case sensitive
}

export function _areBaseKeysPresent(fieldSchema) {
  if (!fieldSchema || typeof fieldSchema !== 'object') {
    return false;
  }
  const baseKeys = ['name', 'title', 'type'];

  for (let k = 0; k < baseKeys.length; k++) {
    if (!fieldSchema.hasOwnProperty(baseKeys[k])) {
      return false;
    }
  }

  return true;
}

export function validateUISchema(udfschema) {
  for (let f = 0; f < udfschema.length; f++) {
    const schema = udfschema[f];
    const keys = Object.keys(schema);
    const optionsKeys = !!schema.options && Object.keys(schema.options);

    if (!_areBaseKeysPresent(schema)) {
      return false;
    }

    if (!_isSupportedType(schema.type)) {
      //required key
      return false;
    }

    // Also, checking if pattern is present if options.keydown_restrictive key is present.
    const pattern = schema.pattern;
    if (
      (typeof pattern !== 'undefined' && !_isSupportedPattern(pattern)) ||
      (schema.options &&
        typeof schema.options.keydown_restrictive !== 'undefined' &&
        typeof pattern === 'undefined')
    ) {
      // optional key
      return false;
    }

    const cmp = optionsKeys && schema.options.cmp;
    if (cmp && !_isSupportedComponent(cmp)) {
      // optional key
      return false;
    }

    if (!_areKeysSupported(keys) || !_areOptionsKeysSupported(optionsKeys)) {
      return false;
    }
  }

  return true;
}

export const SHIPROCKET_FORM_ITEMS = [
  {
    name: 'name',
    title: 'Name',
    required: true,
    type: 'string',
  },
  {
    name: 'address',
    title: 'Address',
    required: true,
    type: 'string',
    options: { cmp: 'textarea' },
  },
  {
    name: 'city',
    title: 'City',
    required: true,
    type: 'string',
  },
  {
    name: 'state',
    title: 'State',
    required: true,
    type: 'string',
  },
  {
    name: 'pincode',
    title: 'Pincode',
    required: true,
    type: 'number',
    minLength: 5,
    maxLength: 6,
    pattern: 'number',
    options: {},
  },
];

export const checkIsShiprocketField = (isShiprocket, key) => {
  if (isShiprocket) {
    const shiprocketKeys = SHIPROCKET_FORM_ITEMS.map((item) => item.name);

    return shiprocketKeys.indexOf(key) > -1;
  }

  return false;
};

export const checkIsMagicCheckoutField = (key) => {
  if (!key) {
    return false;
  }
  const MAGIC_CHECKOUT_FORM_ITEMS = [
    'name',
    'email',
    'phone',
    'zipcode',
    'pincode',
    'city',
    'state',
    'country',
    'address',
    'flat',
    'area',
    'street',
    'house number',
    'colony',
    'town',
    'village',
    'panchayat',
    'post office',
    'building',
    'apartment',
    'society',
    'district',
    'mobile',
    'h. no.',
    'pin code',
    'zip code',
  ];

  /*
   * As per the product requirement, once magic checkout is enabled,
   * we should not allow the fields labelled with reserved words to be placed in prefixes.
   * eg: "Email optional" label is not allowed. "Alternate Email" label is allowed
   */
  return MAGIC_CHECKOUT_FORM_ITEMS.find((item) => key.toLowerCase().startsWith(item));
};
