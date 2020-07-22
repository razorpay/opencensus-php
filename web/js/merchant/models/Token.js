import GenericEntity from './GenericEntity';

export default class Token extends GenericEntity {
  resourceUrl = 'subscription_registration/tokens';

  chargeToken(data) {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/charge`,
      method: 'post',
      data,
    }).then(response => {
      if (response.success) {
        return response.data;
      }
    });
  }

  cancel(customer_id, token_id) {
    return this.makeGenericAjaxCall({
      url: `/customers/${customer_id}/tokens/${token_id}/cancel`,
      method: 'put',
    }).then(response => {
      if (response.success) {
        return response.data;
      }
    });
  }
}
