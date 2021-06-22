import { merchantFetch } from '../../utils/ajax';
import GenericEntity from '../GenericEntity';

export default class MigrationEntity extends GenericEntity {
  constructor() {
    super();
  }

  request = (url, data, progressTracker) => {
    return merchantFetch({
      url: url,
      mode: 'live',
      method: 'post',
      data,
      headers: {
        'Content-Type': 'application/json',
      },
      onUploadProgress: progressTracker,
    });
  };

  resourceUrlPrefix = (domain, entity, endpoint) =>
    `loc/service/twirp/rzp.capital.loc.${domain}.v1.${entity}/${endpoint}`;

  fetchMerchantDetails(data) {
    return this.request(
      this.resourceUrlPrefix('migration', 'MerchantDetailsAPI', 'GetMerchantDetails'),
      data,
    );
  }

  updateMerchantDetails(data) {
    return this.request(
      this.resourceUrlPrefix('migration', 'MerchantDetailsAPI', 'UpdateMerchantDetails'),
      data,
    );
  }
}
