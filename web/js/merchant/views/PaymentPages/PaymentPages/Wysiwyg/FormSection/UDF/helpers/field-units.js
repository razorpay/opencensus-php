/*
 * Extra schema keys supported apart from the ones mentioned in 'schema' object are:
 * title, name, description, required
 *
 * */
const str = {
  label: 'Single Line Text',
  icon: 'alphabet i-fix-alphabet',
  schema: {
    type: 'string',
  },
};

const number = {
  label: 'Number',
  icon: 'number i-fix-number',
  schema: {
    type: 'number',
    pattern: 'number',
    options: {
      keydown_restrictive: true,
    },
  },
};

const email = {
  label: 'Email',
  icon: 'email-at',
  schema: {
    type: 'string',
    pattern: 'email',
  },
};

const phone = {
  label: 'Phone No.',
  icon: 'phone',
  schema: {
    type: 'number',
    pattern: 'phone',
    minLength: '8',
    options: {
      /*
      icon: {
        before: 'i-phone', // TODO: Supporting it?
      },
*/
    },
  },
};

const url = {
  label: 'Link / URL',
  icon: 'link',
  schema: {
    type: 'string',
    pattern: 'url',
  },
};

const textarea = {
  label: 'Large Textarea',
  icon: 'sort i-fix-sort',
  schema: {
    type: 'string',
    options: {
      cmp: 'textarea',
    },
  },
};

const alphabets = {
  label: 'Alphabets',
  icon: 'alphabets',
  schema: {
    type: 'string',
    pattern: 'alphabets',
    options: {
      // keydown_restrictive: true
    },
  },
};

const alphanumeric = {
  label: 'Alphanumeric',
  icon: 'alphanumeric',
  schema: {
    type: 'string',
    pattern: 'alphanumeric',
    options: {
      // keydown_restrictive: true
    },
  },
};

const pan = {
  label: 'PAN Number',
  icon: 'card',
  schema: {
    type: 'string',
    pattern: 'pan',
    options: {},
  },
};

const pincode = {
  label: 'Pincode',
  icon: 'location',
  schema: {
    type: 'number',
    minLength: 5,
    maxLength: 6,
    pattern: 'number',
    options: {},
  },
};

const postcode = {
  label: 'Postcode',
  icon: 'location',
  schema: {
    type: 'number',
    minLength: 5,
    maxLength: 5,
    pattern: 'number',
    options: {},
  },
};

const date = {
  label: 'Date Picker',
  icon: 'date-range',
  schema: {
    type: 'string',
    pattern: 'date',
    options: {
      cmp: 'date', // Note: providing cmp is important, bcoz a simple input field's pattern can also be date. So, to render it as date picker custom component, cmp is required. Similarly, for other field types
    },
  },
};

const dropdown = {
  label: 'Dropdown',
  icon: 'arrow-down i-fix-arrow-down',
  schema: {
    type: 'string',
    enum: [],
    options: {
      cmp: 'select', // Default field for having key:enum is Select
      enum_labels: [],
    },
  },
};

// Note: If any new field is being added with new pattern, ensure that it's added in _isSupportedPattern, _isSupportedComponent and _isSupportedType. Check validateUISchema fn.
export default {
  str,
  number,
  email,
  phone,
  url,
  textarea,
  dropdown,
  alphabets,
  alphanumeric,
  pan,
  pincode,
  date,
  postcode,
};
