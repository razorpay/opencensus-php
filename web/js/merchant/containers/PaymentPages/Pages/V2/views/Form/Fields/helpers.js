import fUnits from './field-units';

/*
* A. Type: text
*    Validation: single line text(string), number, email, phone, url, large text area
*
* B. Type: Select
*     Validation: string
*
* */

export const FIELD_TYPES = [
  {
    label: 'Text',
    icon: 'sort i-fix-sort',
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

export function flattenFIELD_TYPES() {
  const flatten = [];

  for (let i = 0; i < FIELD_TYPES.length; i++) {
    const FIELD = FIELD_TYPES[i];
    if (FIELD.options) {
      for (let j = 0; j < FIELD.options.length; j++) {
        const SUB_FIELD = FIELD.options[j];
        SUB_FIELD.index = String(i) + 1 + String(j); // "01" will become "11" because first option is '--Select--'
        flatten.push(SUB_FIELD);
      }
    } else {
      FIELD.index = String(i);
      flatten.push(FIELD);
    }
  }

  return flatten;
}

// TODO: To add support to return indicies tree
// Note: If schema for a given field is changed, then this fn. will break.
export function mapFieldToIndex(field) {
  let selectedIndexInOptions = null;

  // Removing the fixed schema fields
  const { title, name, required, description, ...schemaFields } = field;

  const fieldTypes = flattenFIELD_TYPES();

  for (let i = 0; i < fieldTypes.length; i++) {
    const FIELD_TYPES_keys = Object.keys(fieldTypes[i].schema);
    const FIELD_TYPES_opts_keys = fieldTypes[i].schema.options
      ? Object.keys(fieldTypes[i].schema.options)
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

    selectedIndexInOptions = fieldTypes.index;
    break;
  }

  if (selectedIndexInOptions === null) {
    throw 'There is mismatch in Schema field.';
  }

  return selectedIndexInOptions;
}

export function getFieldFromIndices(indicesString) {
  let FIELD;
  const indicesTree = String(indicesString).split('');

  if (indicesTree.length === 1) {
    FIELD = FIELD_TYPES[indicesTree[0]];
  } else {
    const sub_options = FIELD_TYPES[indicesTree[0]].options;
    if (!sub_options) {
      return false;
    }
    FIELD = sub_options[indicesTree[1]];
  }

  return FIELD && FIELD.schema;
}

export function constructFieldSchema(fieldData) {
  const { title, required, description, field_type } = fieldData;

  if (isNaN(field_type) || field_type < 0) {
    return false;
  }

  const SCHEMA = getFieldFromIndices(String(field_type));

  if (!title || !SCHEMA) {
    return false;
  }

  return {
    name: title
      .trim()
      .toLowerCase()
      .split(' ')
      .join('_'),
    title,
    required: typeof required !== 'undefined' ? required : undefined,
    description: typeof description !== 'undefined' ? description : undefined,
    ...SCHEMA,
  };
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
  const supportedPatterns = ['email', 'phone', 'number', 'url'];

  return supportedPatterns.indexOf(pattern) > -1;
}

export function _isSupportedComponent(cmp) {
  const supportedCmp = ['select', 'textarea', 'input'];

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
