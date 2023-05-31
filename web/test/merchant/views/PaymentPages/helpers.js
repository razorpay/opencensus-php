import {
  flattenFIELD_TYPES,
  constructFieldSchema,
  validateUISchema,
  _areKeysSupported,
  _areOptionsKeysSupported,
  _isSupportedType,
  _isSupportedPattern,
  _isSupportedComponent,
  _areBaseKeysPresent,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
require('it-each')();
const expect = require('chai').expect;

// Ensures pattern is supported and combination with keydown_restrictive does not block user from typing in that field
function _isPatternSupportedAndNonRestrictive(pattern, isKeydownRestrictive) {
  if (!isKeydownRestrictive) {
    // false or key not defined
    return true;
  }

  if (!pattern) {
    // keydown_restrictive without pattern is invalid schema
    return false;
  }

  // Check whether pattern is supported
  if (!_isSupportedPattern(pattern)) {
    return false;
  }

  // Update these pattern if updated in "static/src/hosted/wysiwig/scripts/validators.js"
  const PATTERN_TO_REGEX_MAPPING = {
    email:
      /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
    number: /^[+-]?([0-9]*[.])?[0-9]+$/,
    phone: /^([0-9]){8,}$/g,
    // amount: /^[0-9]+(.([0-9]){1,2})?$/g, // TODO: Uncomment when multiple amounts supported
    url: /^(?:(?:http|https|ftp):\/\/)?(?:\S+(?::\S*)?@)?(?:(?:(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[0-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))|localhost)(?::\d{2,5})?(?:(\/|\?|#)[^\s]*)?$/i,
  };

  const valuesInSequenceOfTyping = {
    email: ['t', 'testing', 'testing@', 'testing@email', 'testing@email.com'],
    number: ['1', '123'],
    phone: ['9', '987271626'],
    // amount: ['1', '12', '12.', '12.3', '12.43'],
    url: ['t', 'testing', 'testing.com'],
  };

  const p = PATTERN_TO_REGEX_MAPPING[pattern];
  const v = valuesInSequenceOfTyping[pattern];

  if (!v || !p) {
    throw new Error('Pattern or value is missing in the testing data set');
  }
  for (let i = 0; i < v.length; i++) {
    const reg = new RegExp(p);

    if (!reg.test(v[i])) {
      return false;
    }
  }

  // For schema with "options.keydown_restrictive = true", all values must pass to ensure user can type in the field.
  return true;
}

// -------------------------

describe('containers/PaymentPages/../UDF/helpers Fn: Validity of base keys checker', function test() {
  const validBaseKeys = {
    name: 'test name',
    title: 'test title',
    type: 'test type',
  };

  it('dummy schema should have all base keys', function test() {
    const result = _areBaseKeysPresent(validBaseKeys);
    expect(result).to.eql(true);
  });

  const invalidBaseKeys = [
    { name: 'test name' },
    { title: 'test title' },
    { type: 'test type' },
    { title: 'test title', type: 'test type' },
    {},
    null,
    undefined,
    1,
    0,
    true,
    false,
  ];

  it.each(invalidBaseKeys, 'all set of base keys must be invalid.', function test(fieldSet, next) {
    const result = _areBaseKeysPresent(fieldSet);
    expect(result).to.eql(false);

    next();
  });
});

global.window = {
  location: {
    pathname: '',
  },
};

describe('containers/PaymentPages/../UDF/helpers Fn: Validity of base fields in constructed schema', function test() {
  // Total 11 FIELD_TYPES exist in UDF dropdown
  const validFieldSchemas = [
    constructFieldSchema({ title: 'Test title', field_type: '0' }),
    constructFieldSchema({
      title: 'Test title',
      field_type: 1,
      description: 'Test description',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '1',
      required: false,
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '2',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '3',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '4',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '5',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '6',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '7',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '8',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '9',
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '10',
    }),
  ];

  it.each(validFieldSchemas, 'all schemas must have base fields.', function test(schema, next) {
    const result = _areBaseKeysPresent(schema);
    expect(result).to.eql(true);

    next();
  });

  const invalidFieldSchemas = [
    constructFieldSchema({ title: 'Test title' }), // Missing field_type
    constructFieldSchema({ title: 'Test title', field_type: '-1' }), // Bad field_type
    constructFieldSchema({ title: 'Test title', field_type: '12' }), // '12' is 13th field and total FIELD_TYPES is only 12.
    constructFieldSchema({
      title: 'Test title',
      field_type: '06', // Doens't exist
    }),
    constructFieldSchema({
      title: 'Test title',
      field_type: '16', // Doesn't exist
      description: 'Test description',
    }),
    constructFieldSchema({
      // Invalid field_type
      title: 'Test title',
      field_type: -1,
      description: 'Test description',
    }),
    constructFieldSchema({ title: '', field_type: 2, required: false }), // Invalid title
    constructFieldSchema({ field_type: 2, required: false }), // Missing title
    constructFieldSchema({
      // Missing field_type
      title: 'Test title',
      description: 'Test description',
      required: false,
    }),
  ];

  it.each(invalidFieldSchemas, 'all schemas do not have base fields.', function test(schema, next) {
    const result = _areBaseKeysPresent(schema);

    expect(result).to.eql(false);

    next();
  });
});

// -------------------------

describe('containers/PaymentPages/../UDF/helpers Fn: Supported type in schema', function test() {
  const validTypeSet = ['string', 'number'];

  it.each(validTypeSet, 'all schemas must be invalid.', function test(type, next) {
    const result = _isSupportedType(type);
    expect(result).to.eql(true);

    next();
  });

  const invalidTypeSet = [
    'string random',
    'string-number',
    'random_supported_type',
    1,
    0,
    '0',
    true,
    false,
    null,
    undefined,
    '',
  ];

  it.each(invalidTypeSet, 'all types must be invalid.', function test(type, next) {
    const result = _isSupportedType(type);
    expect(result).to.eql(false);

    next();
  });
});

// -------------------------

describe('containers/PaymentPages/../UDF/helpers Fn: Safe Pattern in schema', function test() {
  it.each(
    flattenFIELD_TYPES(),
    'all fields units selectable by user must have safe patterns.',
    function test(field, next) {
      const schema = field.schema;
      const result = _isPatternSupportedAndNonRestrictive(
        schema.pattern,
        !!schema.options && schema.options.keydown_restrictive,
      );

      expect(result).to.eql(true);

      next();
    },
  );

  const BAD_FIELD_TYPES = [
    {
      type: 'string',
      pattern: 'email',
      options: {
        keydown_restrictive: true,
      },
    },
    {
      pattern: 'phone',
      options: {
        keydown_restrictive: true,
      },
    },
    {
      type: 'string',
      pattern: 'url',
      options: {
        keydown_restrictive: true,
      },
    },
  ];

  it.each(
    BAD_FIELD_TYPES,
    'all fields units must have unsafe pattern and keydown_restrictive combination.',
    function test(schema, next) {
      const result = _isPatternSupportedAndNonRestrictive(
        schema.pattern,
        !!schema.options && schema.options.keydown_restrictive,
      );

      expect(result).to.eql(false);

      next();
    },
  );
});

// -------------------------

describe('containers/PaymentPages/../UDF/helpers Fn: Supported cmp in option keys', function test() {
  it.each(
    flattenFIELD_TYPES(),
    'all field units must have supported cmp.',
    function test(field, next) {
      if (!field.options || typeof field.options.cmp === 'undefined') {
        next(); // Skip the field unit where cmp is not defined
      }

      const result = _isSupportedComponent(field.options.cmp);
      expect(result).to.eql(true);

      next();
    },
  );

  const invalidCmpSet = [
    'string random',
    'string-number',
    'random_supported_type',
    1,
    0,
    true,
    false,
    null,
    undefined,
    '',
  ];

  it.each(invalidCmpSet, 'all cmp must be invalid.', function test(cmp, next) {
    const result = _isSupportedComponent(cmp);
    expect(result).to.eql(false);

    next();
  });
});

// -------------------------

describe('containers/PaymentPages/../UDF/helpers Fn: Supported keys in Schema', function test() {
  // All user selectable fields units must have valid supported keys
  it.each(flattenFIELD_TYPES(), 'all keys are supported.', function test(field, next) {
    const keysMap = Object.keys(field.schema);

    const result = _areKeysSupported(keysMap);
    expect(result).to.eql(true);

    next();
  });

  // All user selectable fields units must have valid supported keys in options
  it.each(flattenFIELD_TYPES(), 'all options keys are supported.', function test(field, next) {
    const optionsKeysMap = !!field.schema.options && Object.keys(field.schema.options);

    const result = _areOptionsKeysSupported(optionsKeysMap);
    expect(result).to.eql(true);

    next();
  });

  const invalidSchemaKeys = [
    [
      // Unsupported keys are not allowed in field unit
      'random_key',
    ],
    [
      // Duplicate keys in same field unit not allowed
      'type',
      'type',
    ],
  ];
  const invalidSchemaOptionsKeys = [
    [
      // Unsupported keys are not allowed in field unit
      'random_cmp',
    ],
    [
      // Duplicate keys in same field unit not allowed
      'cmp',
      'cmp',
    ],
  ];

  it.each(invalidSchemaKeys, 'all keys are unsupported.', function test(keySet, next) {
    const result = _areKeysSupported(keySet);
    expect(result).to.eql(false);

    next();
  });

  it.each(
    invalidSchemaOptionsKeys,
    'all options keys are unsupported.',
    function test(keySet, next) {
      const result = _areOptionsKeysSupported(keySet);
      expect(result).to.eql(false);

      next();
    },
  );
});

// -------------------------

describe('containers/PaymentPages/../UDF/helpers Fn: Validity of Schema', function test() {
  // This schema contains exhaustive set of fields units
  const validSchemas = [[]]; // Empty schema is also supported

  // Superset of schema
  const fieldsTypes = flattenFIELD_TYPES();

  for (let i = 0; i < fieldsTypes.length; i++) {
    const rand = Math.floor(Math.random() * 2); // 0 or 1

    const fieldSchema = {
      name: 'test_title',
      title: 'Test title',
      required: !!rand,
      description: rand ? 'Test description' : undefined,
      ...fieldsTypes[i].schema,
    };

    validSchemas.push(fieldSchema);
  }

  it.each(validSchemas, 'all schemas must be valid.', function test(schema, next) {
    const result = validateUISchema(schema);
    expect(result).to.eql(true);

    next();
  });

  // Set of random invalid schemas
  const invalidSchemas = [
    [
      {
        name: 'test name',
        title: 'test title',
        type: 'string',
        options: { randomKey: 'unsupported key' },
      },
    ],
    [
      {
        name: 'test name',
        title: 'test title',
        type: 'string',
        options: { cmp: 'unsupported value' },
      },
    ],
    [
      {
        name: 'test name',
        title: 'test title',
        type: 'string',
        // eslint-disable-next-line no-dupe-keys
        options: { cmp: 'input', cmp: 'duplicate key' },
      },
    ],
    [
      {
        name: 'test name',
        title: 'keydown restrictive present without pattern',
        type: 'string',
        options: { keydown_restrictive: false },
      },
    ],
    [{ name: 'test name', randomKey: 'unsupported key', type: 'number' }],
    [{ name: 'test name', title: 'test title', type: 'unsupported type' }],
  ];

  it.each(invalidSchemas, 'all schemas must be invalid.', function test(schema, next) {
    const result = validateUISchema(schema);
    expect(result).to.eql(false);

    next();
  });
});

// -------------------------
