export const initJSONObj = {
  name: '',
  description: '',
  notify: ['// Eg: Array of Slack identifiers without @'],
  variants: ['// Eg: Array of strings'],
};

export const validatorJSON = {
  name: function(val) {
    if (!val || typeof val !== 'string') {
      return 'name must be non-empty String';
    }
  },
  description: function(val) {
    if (!val || typeof val !== 'string') {
      return 'description must be non-empty String';
    }
  },
  notify: function(val) {
    if (val) {
      let errorMsg;
      if (!(val instanceof Array)) {
        errorMsg = 'notify must be an Array';
      }
      val.forEach(v => {
        if (v.indexOf('@') > -1) {
          errorMsg = '@ is not required in notify Array';
          return false;
        }
      });

      return errorMsg;
    }
  },
  variants: function(val) {
    if (!val || !(val instanceof Array || !val.length)) {
      return 'variants must be a non-empty Array';
    }

    let errorMsg;

    val.forEach(v => {
      const reg = new RegExp(/^[a-z0-9]+$/i);
      if (typeof v !== 'string') {
        errorMsg = 'Each variant must be a String';
        return false;
      } else if (!reg.test(v)) {
        errorMsg = 'variant can only contain Alphanumeric, - and _';
        return false;
      }
    });

    return errorMsg;
  },
};
