const str = {
  label: 'Single line text',
  icon: 'text',
  schema: {
    type: 'string',
  },
};

const number = {
  label: 'Number',
  icon: 'number',
  schema: {
    type: 'number',
  },
};

const email = {
  label: 'Email',
  icon: 'email-at',
  schema: {
    type: 'string',
    pattern: 'email',
    options: {
      keydown_restrictive: false,
    },
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
      keydown_restrictive: false,
      icon: {
        before: 'i-phone',
      },
    },
  },
};

const url = {
  label: 'Link / URL',
  icon: 'link',
  schema: {
    type: 'string',
    pattern: 'url',
    options: {
      keydown_restrictive: false,
    },
  },
};

const textarea = {
  label: 'Large text area',
  icon: 'sort',
  type: 'string',
  options: {
    cmp: 'textarea',
  },
};

const dropdown = {
  label: 'Dropdown',
  schema: {
    type: 'string',
    enum: [],
    options: {
      cmp: 'select', // Default field for type:enum is Select
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
