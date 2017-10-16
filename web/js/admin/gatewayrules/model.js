import { prevent } from 'util/index';
import CollectionItem from 'model/collectionItem';
import { adminPost, adminDelete } from 'util/fetch';
import { toJS } from 'mobx';

const defaultProps = {
  type: '',
  method: '',
};

export default class GatewayRule extends CollectionItem {
  constructor(collection, props = defaultProps) {
    super(collection, props);
    this.bind(['save', 'delete']);
  }

  save(body) {
    if (body.iins) {
      body.iins = body.iins.split(',');
    }
    return this.request(
      adminPost(
        {
          data: {
            body,
          },
        },
        {
          params: {
            route_name: 'gateway_create_rule',
            mode: this.collection.filters.mode,
          },
        }
      )
    ).then(data => {
      if (data) {
        this.collection.items.push(this);
      }
    });
  }

  delete(e) {
    prevent(e);
    return this.request(
      adminDelete({
        route_name: 'gateway_delete_rule',
        mode: this.collection.filters.mode,
        url_params: {
          id: this.id,
        },
      })
    ).then(data => {
      if (data) {
        this.collection.items.remove(this);
      }
    });
  }
}
