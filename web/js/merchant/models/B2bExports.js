import { merchantFetch } from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

class B2bExports extends GenericEntity {
  resourceUrl = 'international';

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

  getBeneficiaryDetails() {
    const params = {
      url: `${this.resourceUrl}/virtual_accounts/beneficiary`,
      method: 'get',
    };
    return merchantFetch(params);
  }

  createPayout(data) {
    const params = {
      url: `${this.resourceUrl}/virtual_accounts/payout`,
      method: 'post',
      data,
    };
    return merchantFetch(params);
  }
}

export default B2bExports;
