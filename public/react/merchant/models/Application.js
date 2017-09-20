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
    let id = params.id;

    let data = {};

    data.route_name = this.connectedListRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item => {
        item.logo_url = this.formatLogoUrl(item.logo_url);
        return new Application(item);
      });
      return response;
    });
  }

  fetch(params = {}) {
    return super.fetch(params).then(data => {
      data.logo_url = this.formatLogoUrl(data.logo_url);
      return { id: this.id, ...data };
    });
  }

  fetchAll(params = {}) {
    return super.fetchAll(params).then(response => {
      response.data.items = response.data.items.map(item => {
        item.logo_url = this.formatLogoUrl(item.logo_url);
        return new Application(item);
      });

      return response;
    });
  }

  create(params = {}, fileName) {
    let formData = new FormData();
    formData.append('route_name', 'oauth_application_create');
    for (let key in params) {
      formData.append(`body[${key}]`, params[key]);
    }

    formData.append('file', params.file);
    formData.append('file_name', fileName);

    return ajax({
      url: '/user/generic',
      method: 'post',
      data: formData,
      appendModeInURL: false,
      processData: false,
      contentType: false,
    })
      .then(response => {
        response.data.logo_url = this.formatLogoUrl(response.data.logo_url);
        return response;
      })
      .then(response => {
        return new Application(response.data);
      });
  }

  update(params = {}, fileName) {
    let formData = new FormData();
    for (let key in params) {
      if (key === 'client_details') {
        formData = this.formatClientDetails(formData, key, params);
      } else if (key === 'file') {
        formData.append(key, params[key]);
      } else {
        formData.append(`body[${key}]`, params[key]);
      }
    }

    formData.append('route_name', 'oauth_application_update');
    formData.append('file_name', fileName);
    formData.append('url_params', JSON.stringify({ '{id}': this.id }));

    return ajax({
      url: '/user/generic',
      method: 'post',
      data: formData,
      appendModeInURL: false,
      processData: false,
      contentType: false,
    })
      .then(response => {
        response.data.logo_url = this.formatLogoUrl(response.data.logo_url);
        return response;
      })
      .then(response => {
        return new Application(response.data);
      });
  }

  formatLogoUrl(logoUrl) {
    if (logoUrl !== null) {
      if (logoUrl !== null && !/^http/.test(logoUrl)) {
        var cdnName =
          window.location.hostname.indexOf('-') !== -1 ? 'betacdn' : 'cdn';
        logoUrl =
          'https://' +
          cdnName +
          '.razorpay.com' +
          logoUrl.replace(/\.([^\.]+$)/, '_medium.$1');
      }
    }

    return logoUrl;
  }

  formatClientDetails(formData, key, params) {
    formData.append(`body[${key}][0][id]`, params[key][0]['id']);

    let urls = params[key][0]['redirect_url'];

    if (urls instanceof Array) {
      urls.forEach(function(e) {
        formData.append(`body[${key}][0][redirect_url][]`, e);
      });
    }

    formData.append(`body[${key}][1][id]`, params[key][1]['id']);

    urls = params[key][1]['redirect_url'];

    if (urls instanceof Array) {
      urls.forEach(function(e) {
        formData.append(`body[${key}][1][redirect_url][]`, e);
      });
    }

    return formData;
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
