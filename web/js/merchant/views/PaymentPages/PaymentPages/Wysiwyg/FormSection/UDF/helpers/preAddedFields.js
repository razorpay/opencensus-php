import fUnits from './field-units';

export const FIXED_FIELDS = {
  get email() {
    return {
      name: 'email',
      required: true,
      title: 'Email',
      ...fUnits.email.schema,
    };
  },

  get phone() {
    return {
      name: 'phone',
      title: 'Phone',
      required: true,
      ...fUnits.phone.schema,
    };
  },

  // Below fields are used to prefill empty donation template

  get name() {
    return {
      name: 'name',
      title: 'Name',
      required: true,
      ...fUnits.str.schema,
    };
  },

  get address() {
    return {
      name: 'address',
      title: 'Address',
      required: true,
      ...fUnits.textarea.schema,
    };
  },

  get city() {
    return {
      name: 'city',
      title: 'City',
      required: true,
      ...fUnits.alphabets.schema,
    };
  },

  get pincode() {
    return {
      name: 'pincode',
      title: 'Pincode',
      required: true,
      ...fUnits.pincode.schema,
    };
  },

  get postcode() {
    return {
      name: 'postcode',
      title: 'PostCode',
      required: true,
      ...fUnits.postcode.schema,
    };
  },

  get state() {
    return {
      name: 'state',
      title: 'State',
      required: true,
      ...fUnits.alphabets.schema,
    };
  },

  get primaryRefId() {
    return {
      name: 'pri__ref__id',
      title: 'Primary Reference ID',
      required: true,
      ...fUnits.str.schema,
    };
  },

  get secondaryRefId() {
    return {
      name: 'sec__ref__id_1',
      title: 'Secondary Reference ID',
      required: true,
      ...fUnits.str.schema,
    };
  },

  get lateFeeDueDate() {
    return {
      name: 'late__fee__due__date_1',
      title: 'Late Payment Due Date',
      required: true,
      ...fUnits.date.schema,
    };
  },
};
