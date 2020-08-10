// All your environment specific secrets/keys goes into respective environment object
module.exports = {
  development: {
    assetsUrl: '',
    port: 8000,
    projectType: 'react',
  },
  staging: {
    projectType: 'react',
    assetsUrl: '',
  },
  production: {
    projectType: 'react',
    assetsUrl: '',
  },
};
