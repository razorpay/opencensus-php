import GenericEntity from './GenericEntity';

export default class BMerchant extends GenericEntity {
  resourceUrl = 'd2c_bureau_details';
  resourceFields = [
    'id',
    'first_name',
    'last_name',
    'state',
    'contact_mobile',
    'email',
    'pan',
    'address',
    'city',
    'pincode',
    'gender',
    'date_of_birth',
  ];

  getResourceMethod() {
    return this.isNew ? 'post' : 'patch';
  }

  fetch() {
    let Klass = this.constructor;
    return this.makeGenericAjaxCall({
      data: {},
      url: this.resourceUrl,
      method: 'post',
    }).then(response => {
      return new Klass(response.data).deserialize();
    });
  }
}
