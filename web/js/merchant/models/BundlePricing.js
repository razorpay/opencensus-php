import GenericEntity from 'merchant/models/GenericEntity';
import { getUser } from 'merchant/store';

export default class BundlePricing extends GenericEntity {
  user = getUser();
  resourceUrl = `pricing/merchant/subscriptions`;

  fetchEnrollmentStatus = async () => {
    const response = await this.makeGenericAjaxCall({
      method: 'get',
      mode: 'live',
      url: `${this.resourceUrl}/exists`,
    });

    return {
      hasEnrolled: response?.data?.response?.exists === 'true',
      message: response?.data?.response?.message ?? null,
    };
  };
}
