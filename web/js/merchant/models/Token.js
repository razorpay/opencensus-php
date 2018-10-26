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
}
