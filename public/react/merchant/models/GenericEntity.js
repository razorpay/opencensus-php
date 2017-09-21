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
    let { id, ...queryParams } = params;
    let data = {
      query_params: JSON.stringify(queryParams),
    };

    if (id) {
      return this.fetch(id, data).then(response => {
        return {
          data: {
            items: [response],
          },
        };
      });
    }

    data.route_name = this.listRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass(item).deserialize()
      );
      return response;
    });
  }

  fetch(id, data = {}, queryParams = {}) {
    const Klass = this.constructor;
    data.url_params = JSON.stringify({
      '{id}': id,
    });
    data.query_params = JSON.stringify(queryParams);
    data.route_name = this.detailsRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      return new Klass(response.data).deserialize();
    });
  }

  save(params = null) {
    const Klass = this.constructor;
    params = params || this.serialize();
    let url = this.resourceUrl;
    let method = this.getResourceMethod();
    let { id = this.id, ...bodyParams } = params;
    let data = {
      body: bodyParams,
      route_name: this.getRouteName(),
    };

    if (id) {
      data.url_params = JSON.stringify({
        '{id}': id,
      });
    }

    return this.makeGenericAjaxCall({
      method,
      data,
    }).then(response => {
      return new Klass(response.data).deserialize();
    });
  }

  delete() {
    return this.makeGenericAjaxCall({
      method: 'delete',
      data: {
        route_name: this.deleteRouteName,
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      },
    });
  }

  makeGenericAjaxCall({
    data,
    method = 'get',
    appendModeInURL = false,
    appendModeInQueryParam = true,
  }) {
    return ajax({
      url: this.resourceUrl,
      method,
      data,
      appendModeInQueryParam,
      appendModeInURL,
    });
  }
}
