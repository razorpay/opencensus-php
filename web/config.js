// All your environment specific secrets/keys goes into respective environment object
module.exports = {
  development: {
    assetsUrl: '',
    port: 8000,
    projectType: 'react',
    invisibleCaptcha: {
      value: 'Faked',
    },
    v3Captcha: {
      value: 'Faked',
    },
  },
  staging: {
    projectType: 'react',
    assetsUrl: '',
    invisibleCaptcha: {
      value: 'Faked',
    },
    v3Captcha: {
      value: 'Faked',
    },
  },
  production: {
    projectType: 'react',
    assetsUrl: '',
    invisibleCaptcha: {
      value: 'Faked',
    },
    v3Captcha: {
      value: 'Faked',
    },
  },
};
