import GenericEntity from './GenericEntity';

export default class Transfer extends GenericEntity {
  listRouteName = 'transfer_fetch_multiple';
  detailsRouteName = 'transfer_fetch';

  reverse(data) {
    let params = { '{id}': this.id };

    return this.makeGenericAjaxCall({
      method: 'post',
      data: {
        route_name: 'transfer_reversal',
        url_params: JSON.stringify(params),
        body: {
          ...data,
        },
      },
    });
  }

  fetchReversals() {
    let data = {};
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });
    data.route_name = 'transfer_reversal';
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Transfer(item).deserialize()
      );
      return response;
    });
  }
}
