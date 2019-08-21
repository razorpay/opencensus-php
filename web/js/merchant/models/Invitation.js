import GenericEntity from './GenericEntity';

export default class Invitation extends GenericEntity {
  resourceUrl = 'invitations';

  fetchAll(params = {}) {
    return this.makeGenericAjaxCall({
      url: this.resourceUrl,
      data: { mode: 'live' },
    }).then(response => ({
      data: {
        items: [...response.data],
      },
    }));
  }
}
