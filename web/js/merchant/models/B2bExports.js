import { merchantFetch } from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

class B2bExports extends GenericEntity {
  resourceUrl = 'international';

  activate() {
    const params = {
      url: `${this.resourceUrl}/virtual_accounts`,
      method: 'post',
      data: { accept_b2b_tnc: 1 },
    };
    return merchantFetch(params);
  }

  accounts() {
    const params = {
      url: `${this.resourceUrl}/virtual_accounts`,
      method: 'get',
    };
    return merchantFetch(params);
  }

  getBalance(currency) {
    const params = {
      url: `${this.resourceUrl}/virtual_accounts/balance/${currency}`,
      method: 'get',
    };
    return merchantFetch(params);
  }
}

export default B2bExports;
