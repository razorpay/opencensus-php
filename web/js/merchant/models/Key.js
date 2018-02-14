import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils';

const rollKeyFields = ['id', 'delay_roll'];

export default class Key extends GenericEntity {
  resourceUrl = 'keys';

  getRouteName() {
    return this.isNew ? 'merchant_create_key' : 'merchant_replace_key';
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put';
  }

  resourceFields() {
    return this.isNew ? [] : rollKeyFields;
  }
}
