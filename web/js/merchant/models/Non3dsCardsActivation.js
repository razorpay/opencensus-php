// eslint-disable-next-line import/no-cycle
import GenericEntity from './GenericEntity';
import { merchantFetch } from 'merchant/utils/ajax';

export default class Non3dsCardsActivationResource extends GenericEntity {
  resourceUrl = 'merchant';

  status() {
    const url = `${this.resourceUrl}/get_non_3ds_details`;
    return this.makeGenericAjaxCall({ url });
  }

  enable() {
    const url = `${this.resourceUrl}/enable_non_3ds`;
    return merchantFetch({
      url,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      data: {},
    });
  }

  disable() {
    const url = `${this.resourceUrl}/features/update`;
    return merchantFetch({
      url,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        enable: ['accept_only_3ds_payments'],
        disable: [],
      },
    });
  }
}
