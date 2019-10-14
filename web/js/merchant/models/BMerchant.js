import GenericEntity from './GenericEntity';

export default class BMerchant extends GenericEntity {
  resourceUrl = 'creditPull';

  fetch() {
    let sameplePayload = {
      firstName: 'Devansh',
      lastName: 'Dwivedi',
      merchant_state: 'KA',
      mobile: '9899115434',
      email: 'mikael@gmail.com',
      pan: 'AWVPD201H',
      line1: '1st Floor, HSR Heights',
      city: 'Bengaluru',
      state: 'KA',
      pinCode: '560017',
    };

    let Klass = this.constructor;

    return this.makeGenericAjaxCall({
      data: {},
      url: 'es/scheduled_pricing',
    }).then(() => {
      return new Klass(sameplePayload).deserialize();
    });
  }
}
