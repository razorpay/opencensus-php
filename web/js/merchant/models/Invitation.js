import GenericEntity from './GenericEntity';

export default class Invitation extends GenericEntity {
  resourceUrl = 'invitations';

  fetchAll() {
    return this.makeGenericAjaxCall({
      url: this.resourceUrl,
      data: { mode: 'live' },
    }).then(response => ({
      data: {
        items: [...response.data],
      },
    }));
  }

  delete(...args) {
    return super.delete(...args).then(response => ({
      ...response.data,
    }));
  }

  update({ id, ...data }) {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${id}`,
      method: 'patch',
      data: {
        ...data,
        // invitation tables not synced on backend
        mode: 'live',
      },
    }).then(response => ({
      ...response.data,
    }));
  }

  resend() {
    const { id, ...data } = this.getPayload();
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${id}/resend`,
      method: 'put',
      data: {
        ...data,
        // invitation tables not synced on backend
        mode: 'live',
      },
    }).then(response => ({
      ...response.data,
    }));
  }
}
