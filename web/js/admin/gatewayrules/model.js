import { prevent } from 'util/index';
import CollectionItem from 'model/collectionItem';
import { adminDelete } from 'util/fetch';

export default class GatewayRule extends CollectionItem {
  constructor(collection, props) {
    super(collection, props);
    this.bind(['save', 'delete']);
  }

  save() {}

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
