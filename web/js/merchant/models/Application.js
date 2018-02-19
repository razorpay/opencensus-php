import GenericEntity from './GenericEntity';
import { merchantFetch } from 'rzp/utils/ajax';

const editFields = ['id', 'delay_roll'];
const newFields = ['name', 'website'];

export default class Application extends GenericEntity {
  resourceUrl = 'oauth/applications';

  getResourceMethod() {
    return 'post';
  }

  fetchConnected(params = {}) {
    let id = params.id;

    return merchantFetch('oauth/tokens/').then(response => {
      response.data.items = response.data.items.map(item => {
        item.application.logo_url = this.formatLogoUrl(
          item.application.logo_url
        );
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
    for (let key in params) {
      formData.append(key, params[key]);
    }

    formData.append(fileName, params.file);

    return merchantFetch({
      url: 'oauth/applications',
      method: 'post',
      data: formData,
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
        formData.append(fileName, params[key]);
      } else {
        formData.append(`${key}`, params[key]);
      }
    }

    return merchantFetch({
      url: `oauth/applications/${this.id}`,
      method: 'post',
      data: formData,
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
    formData.append(`${key}[0][id]`, params[key][0]['id']);

    let urls = params[key][0]['redirect_url'];

    if (urls instanceof Array) {
      urls.forEach(function(e) {
        formData.append(`${key}[0][redirect_url][]`, e);
      });
    }

    formData.append(`${key}[1][id]`, params[key][1]['id']);

    urls = params[key][1]['redirect_url'];

    if (urls instanceof Array) {
      urls.forEach(function(e) {
        formData.append(`${key}[1][redirect_url][]`, e);
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
    return merchantFetch({
      url: `oauth/tokens/${this.id}/revoke`,
      method: 'put',
    }).then(data => {
      return { id, ...data };
    });
  }
}
