import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';

const editFields = ['id', 'delay_roll'];
const newFields = ['name', 'website'];

export default class Application extends GenericEntity {
  listRouteName = 'oauth_application_fetch_multiple';
  connectedListRouteName = 'oauth_token_fetch_multiple';
  deleteRouteName = 'oauth_application_delete';
  revokeRouteName = 'oauth_token_revoke';
  detailsRouteName = 'oauth_application_fetch';

  getResourceMethod() {
    return 'post';
  }

  fetchConnected(params = {}) {
    const Klass = this.constructor;
    let id = params.id;

    let data = {};

    data.route_name = this.connectedListRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(
        item => new Application(item)
      );
      return response;
    });
  }

  create(params = {}) {
    const Klass = this.constructor;
    // let url = this.resourceUrl;
    let formData = new FormData();
    formData.append('route_name', 'oauth_application_create');
    for (let key in params) {
      formData.append(`body[${key}]`, params[key]);
    }

    return ajax({
      url: '/user/generic',
      method: 'post',
      data: formData,
      appendModeInURL: false,
      processData: false,
      contentType: false,
    }).then(response => {
      return new Application(response.data);
    });
  }

  update(params = {}) {
    let url = this.resourceUrl;
    let method = 'post';
    let data = {
      route_name: 'oauth_application_update',
      body: params,
      url_params: JSON.stringify({
        '{id}': this.id,
      }),
    };
    return this.makeGenericAjaxCall({
      method,
      data,
    }).then(response => {
      return new Application(response.data);
    });
  }

  delete() {
    return super.delete().then(data => {
      return { id: this.id, ...data };
    });
  }

  revokeToken(params) {
    var id = this.id;
    return this.makeGenericAjaxCall({
      method: 'put',
      data: {
        route_name: this.revokeRouteName,
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      },
    }).then(data => {
      return { id, ...data };
    });
  }
}
