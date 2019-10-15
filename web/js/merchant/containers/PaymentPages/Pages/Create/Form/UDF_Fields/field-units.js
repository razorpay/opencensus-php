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
};
