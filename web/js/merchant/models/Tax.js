import GenericEntity from './GenericEntity';
import { merchantFetch } from 'rzp/utils/ajax';

export default class Tax extends GenericEntity {
  resourceFields = ['id', 'name', 'rate', 'rate_type'];

  resourceUrl = 'taxes';

  /**
   * Fetches GST taxes from the meta API.
   * @return {Promise}
   */
  static fetchGSTTaxes() {
    return merchantFetch({
      url: 'taxes/meta/gst_taxes',
      method: 'get',
    });
  }
}
