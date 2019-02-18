import { AppStore } from 'admin/razorx/store';

export const initJSONObj = {
  description: '',
  environment: AppStore.environmentList[0] || 'beta',
  mode: 'test',
  feature_id: 0,
  segments: [
    {
      variant: '',
      type: '// Eg: String: whitelist, blacklist, ramp, context-ramp',
      ids: [],
      weight: '// Eg: Number: 1(=> 0.001%)',
    },
  ],
};

const required = {
  description: function(val) {
    if (!val || typeof val !== 'string') {
      return 'description must be non-empty String';
    }
  },
  environment: function(val) {
    if (!val || AppStore.environmentList.indexOf(val) === -1) {
      return (
        'environment must be one of ' + JSON.stringify(AppStore.environmentList)
      );
    }
  },
  mode: function(val) {
    if (!val || ['test', 'live'].indexOf(val) === -1) {
      return 'mode must be one of [test, live]';
    }
  },
  feature_id: function(val) {
    if (typeof val === 'undefined' || typeof val !== 'number') {
      return 'feature id must be a valid Number';
    }
  },
  segments: function(val) {
    if (!val || !(val instanceof Array || !val.length)) {
      return 'segments must be a non-empty Array';
    }

    let errorMsg;

    val.forEach(s => {
      const isVariantInvalid = !s.variant || typeof s.variant !== 'string';
      if (isVariantInvalid) {
        errorMsg = 'variant must be a non-empty String';
        return false;
      }

      const isTypeInvalid =
        !s.type ||
        ['whitelist', 'blacklist', 'ramp', 'context-ramp'].indexOf(s.type) ===
          -1;
      if (isTypeInvalid) {
        errorMsg =
          'type must be one of [whitelist, blacklist, ramp, context-ramp]';
        return false;
      }

      if (['whitelist', 'blacklist', 'context-ramp'].indexOf(s.type) !== -1) {
        const isIdInvalid = !s.ids || !(s.ids instanceof Array);
        if (isIdInvalid) {
          errorMsg = 'Invalid Array of ids';
          return false;
        }
      } else if (s.ids !== void 0) {
        errorMsg = 'ids not required for ' + s.type;
        return false;
      }

      if (['ramp', 'context-ramp'].indexOf(s.type) !== -1) {
        const isWeightInvalid =
          !s.weight ||
          typeof s.weight !== 'number' ||
          s.weight < 1 ||
          s.weight > 100000;
        if (isWeightInvalid) {
          errorMsg = 'weight must be a valid Number between [1-100000]';
          return false;
        }
      } else if (s.weight !== void 0) {
        errorMsg = 'weight not required for ' + s.type;
        return false;
      }
    });

    return errorMsg;
  },
};

export default {
  required,
};
