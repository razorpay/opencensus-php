/*
* Extra schema keys supported apart from the ones mentioned in 'schema' object are:
* title, name, description, required
*
* */
const str = {
  label: 'Single line text',
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
  label: 'Large text area',
  icon: 'sort i-fix-sort',
  schema: {
    type: 'string',
    options: {
      cmp: 'textarea',
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

export default {
  str,
  number,
  email,
  phone,
  url,
  textarea,
  dropdown,
};
