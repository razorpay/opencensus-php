import fUnits from './field-units';

let getUser;

if (typeof window !== 'undefined') {
  getUser = require('merchant/store').getUser;
}

/*
* A. Type: text
*    Validation: single line text(string), alphabets, alphanumeric, number, email, phone, url, large text area, pan, pincode
*
* B. Type: Select
*     Validation: string
*
* */

export function getFieldTypes() {
  let FIELD_TYPES = [
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

  if (getUser && getUser().toShowExtraFieldsInPP) {
    FIELD_TYPES = [
      {
        label: 'Text',
        icon: 'sort i-fix-sort',
        options: [
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
        ],
      },
      fUnits.dropdown,
    ];
  }

  return FIELD_TYPES;
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

    let isMismatch = false;

    if (
      FIELD_TYPES_keys.length !== field_keys.length ||
      FIELD_TYPES_opts_keys.length !== field_opts_keys.length
    ) {
      isMismatch = true;
    }

    if (isMismatch) {
      continue;
    }

    for (let j = 0; j < FIELD_TYPES_keys.length; j++) {
      // EXCEPTION 1: values of schema.options is checked in next for-each block.
      // EXCEPTION 2: value for enum is not to be compared as it's an array, it can be skipped and options.cmp will handle existence of 'key:enum'
      if (['settings', 'options', 'enum'].indexOf(FIELD_TYPES_keys[j]) > -1) {
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
      continue;
    }

    for (let j = 0; j < FIELD_TYPES_opts_keys.length; j++) {
      // EXCEPTION 1: values of schema.options.enum_label will always be different. So, skipped because relying on schema.options.cmp == 'select'
      if (['enum_labels'].indexOf(FIELD_TYPES_opts_keys[j]) > -1) {
        continue;
      }

      const valueInFieldSchemaOptions =
        schemaFields.options && schemaFields.options[FIELD_TYPES_opts_keys[j]];
      const valueInFieldMapSchemaOptions =
        fieldTypes[i].schema.options &&
        fieldTypes[i].schema.options[FIELD_TYPES_opts_keys[j]];

      if (valueInFieldSchemaOptions !== valueInFieldMapSchemaOptions) {
        isMismatch = true;
        break;
      }
    }

    if (isMismatch) {
      continue;
    }

    selectedIndexInOptions = fieldTypes[i].level;
    break;
  }

  if (selectedIndexInOptions === null) {
    throw 'There is mismatch in Schema field.';
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
    name: prettyTitle
      .trim()
      .toLowerCase()
      .split(' ')
      .join('_'),
    title: prettyTitle,
    required: typeof required !== 'undefined' ? required : undefined,
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
  ];

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
