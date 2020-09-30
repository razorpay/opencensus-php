import GenericEntity from './GenericEntity';

export default class RegistrationLink extends GenericEntity {
  resourceUrl = 'subscription_registration/auth_links';

  constructor(data) {
    super(data);

    this.status = data && data.auth_link_status;
  }

  cancel() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/cancel`,
      method: 'post',
    }).then((response) => {
      return new RegistrationLink(response.data).deserialize();
    });
  }
}
