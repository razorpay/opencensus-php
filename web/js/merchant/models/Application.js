import GenericEntity from './GenericEntity';
import { merchantFetch } from 'merchant/utils/ajax';

export default class Application extends GenericEntity {
  resourceUrl = 'oauth/applications';

  getResourceMethod() {
    return 'post';
  }

  fetchConnected(params = {}) {
    // eslint-disable-next-line
    let id = params.id;

    return merchantFetch('oauth/tokens/').then((response) => {
      response.data.items = response.data.items.map((item) => {
        item.application.logo_url = this.formatLogoUrl(item.application.logo_url);
        return new Application(item);
      });
      return response;
    });
  }

  fetchOauthConnectedApplications() {
    return merchantFetch('oauth/submerchant/applications').then((response) => {
      response.data.items = response.data.items.map((item) => {
        item.logo_url = this.formatLogoUrl(item.logo_url);
        return new Application(item);
      });
      return response;
    });
  }

  revokeOauthApplicationAccess(id) {
    return merchantFetch({
      mode: 'live',
      url: `oauth/applications/${id}/revoke`,
      method: 'put',
    }).then((data) => {
      return { id, ...data };
    });
  }

  fetch(params = {}) {
    return super.fetch(params).then((data) => {
      data.logo_url = this.formatLogoUrl(data.logo_url);
      return { id: this.id, ...data };
    });
  }

  fetchAll(params = {}) {
    return super.fetchAll(params).then((response) => {
      response.data.items = response.data.items.map((item) => {
        item.logo_url = this.formatLogoUrl(item.logo_url);
        return new Application(item);
      });

      return response;
    });
  }

  fetchPartnerApplication() {
    return merchantFetch(`${this.resourceUrl}/partner`).then((response) => {
      return response.data;
    });
  }

  create(params = {}, fileName) {
    const formData = new FormData();
    // eslint-disable-next-line guard-for-in
    for (const key in params) {
      formData.append(key, params[key]);
    }

    formData.append(fileName, params.file);

    return merchantFetch({
      url: 'oauth/applications',
      mode: 'live',
      method: 'post',
      data: formData,
    })
      .then((response) => {
        response.data.logo_url = this.formatLogoUrl(response.data.logo_url);
        return response;
      })
      .then((response) => {
        return new Application(response.data);
      });
  }

  update(params = {}, fileName) {
    let formData = new FormData();
    for (const key in params) {
      if (key === 'client_details') {
        formData = this.formatClientDetails(formData, key, params);
      } else if (key === 'file') {
        formData.append(fileName, params[key]);
      } else {
        formData.append(`${key}`, params[key]);
      }
    }

    // pass the referral metadata

    const clientID = params.client_details?.[1]?.id;
    const redirectUri = params.client_details?.[1]?.redirect_url?.[0];

    formData.append('referral_metadata[application_id]', this.id);
    formData.append('referral_metadata[client_id]', clientID);
    formData.append('referral_metadata[partner_id]', window.rzp_user?.merchant?.id);
    formData.append('referral_metadata[redirect_uri]', redirectUri);
    formData.append('referral_metadata[scope]', 'read_write');

    return merchantFetch({
      url: `oauth/applications/${this.id}`,
      mode: 'live',
      method: 'post',
      data: formData,
    })
      .then((response) => {
        response.data.logo_url = this.formatLogoUrl(response.data.logo_url);
        return response;
      })
      .then((response) => {
        return new Application(response.data);
      });
  }

  formatLogoUrl(logoUrl) {
    if (logoUrl !== null) {
      if (logoUrl !== null && !/^http/.test(logoUrl)) {
        const cdnName = window.location.hostname.indexOf('-') !== -1 ? 'betacdn' : 'cdn';
        logoUrl =
          // eslint-disable-next-line prefer-template, no-useless-escape
          'https://' + cdnName + '.razorpay.com' + logoUrl.replace(/\.([^\.]+$)/, '_medium.$1');
      }
    }

    return logoUrl;
  }

  formatClientDetails(formData, key, params) {
    formData.append(`${key}[0][id]`, params[key][0].id);

    let urls = params[key][0].redirect_url;

    if (urls instanceof Array) {
      urls.forEach((e) => {
        formData.append(`${key}[0][redirect_url][]`, e);
      });
    }

    formData.append(`${key}[1][id]`, params[key][1].id);

    urls = params[key][1].redirect_url;

    if (urls instanceof Array) {
      urls.forEach((e) => {
        formData.append(`${key}[1][redirect_url][]`, e);
      });
    }

    return formData;
  }

  delete() {
    return super.delete().then((data) => {
      return { id: this.id, ...data };
    });
  }

  revokeToken(_params) {
    const id = this.id;
    return merchantFetch({
      mode: 'live',
      url: `oauth/tokens/${this.id}/revoke`,
      method: 'put',
    }).then((data) => {
      return { id, ...data };
    });
  }
}
