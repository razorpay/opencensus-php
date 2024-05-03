export const UNICOMMERCE_INFO_POINTS = [
  {
    instructions: {
      heading: 'Credentials required for ‘Admin’ user on Unicommerce with access to facilities.',
      points: ['Steps to create ‘Admin’ user if not already available:'],
      links: ['https://documentation.unicommerce.com/docs/faq-uniware.html'],
    },
  },
  {
    instructions: {
      heading: 'Magic Checkout requires username and password to access Unicommerce APIs.',
      points: [
        'Shipping statuses will auto sync to Magic Checkout.',
        'Get improved RTO Protection and Intelligence.',
      ],
    },
  },
  {
    instructions: {
      heading: 'Tenant to be picked from Unicommerce domain URL.',
      points: ['e.g. superstore.unicommerce.com [tenant = superstore]'],
    },
  },
];

export const FORM_FIELDS = [
  {
    name: 'username',
    label: 'Username',
    placeholder: 'Enter username',
    helpText: 'Email address for Unicommerce account',
  },
  {
    name: 'password',
    label: 'Password',
    placeholder: 'Enter password',
    helpText: 'Password for Unicommerce account',
  },
  {
    name: 'tenant',
    label: 'Tenant',
    placeholder: 'Enter tenant',
    helpText: 'Unicommerce account code which comes in URL after login into Unicommerce',
  },
];

export const USERNAME_INVALID_REGEX = /^[^.]*$/;
