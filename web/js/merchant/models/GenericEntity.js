// Todo: delete this file, it's available in @dashboard/shared-utils
import Entity from './Entity';
import ajax from 'merchant/utils/ajax';

/*
  Abstract class for most CRUD entities. The base Entity has methods like
  - instance.fetchAll(params)
  - instance.fetch(params)
  - instance.save()
  - instance.delete()
*/

// `GenericEntity will replace the `Entity` when all routes are migrated to `/generic` routes
export default class GenericEntity extends Entity {
  resourceUrl = '/user/generic';

  fetchAll(params = {}) {
    const Klass = this.constructor;
    const { id, ...queryParams } = params;
    const data = this.listRouteName
      ? { query_params: JSON.stringify(queryParams) }
      : { ...queryParams };

    if (id) {
      return this.fetch(id, data).then((response) => {
        return {
          data: {
            items: [response],
          },
        };
      });
    }

    data.route_name = this.listRouteName;
    return this.makeGenericAjaxCall({ data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass(item).deserialize());

      return response;
    });
  }

  fetch(id, data = {}, queryParams = {}) {
    const Klass = this.constructor;
    const url = `${this.resourceUrl}/${id}`;
    data = { ...data, ...queryParams };

    return this.makeGenericAjaxCall({ data, url }).then((response) => {
      return new Klass(response.data).deserialize();
    });
  }

  save(params = null, httpData, noPayloadDiffFromStash) {
    const Klass = this.constructor;
    params = params || this.serialize(noPayloadDiffFromStash);
    const method = this.getResourceMethod();
    const { id = this.id, ...bodyParams } = params;
    const url = `${this.resourceUrl}/${id || ''}`;
    const data = { ...bodyParams };

    return this.makeGenericAjaxCall({
      url,
      method,
      data,
      httpData,
    })
      .then((response) => {
        return new Klass(response.data).deserialize();
      })
      .catch((error) => {
        throw error;
      });
  }

  delete(data) {
    return this.makeGenericAjaxCall({
      method: 'delete',
      url: `${this.resourceUrl}/${this.id}`,
      data,
    });
  }

  makeGenericAjaxCall({
    url = this.resourceUrl,
    data = {},
    params,
    method = 'get',
    appendModeInURL = !Boolean(data.route_name),
    appendModeInQueryParam = Boolean(data.route_name),
    httpData = {},
  }) {
    return ajax(
      {
        url,
        method,
        data,
        params,
        appendModeInQueryParam,
        appendModeInURL,
        ...httpData,
      },
      {},
      data.route_name ? '' : '/merchant/api',
    );
  }
}
