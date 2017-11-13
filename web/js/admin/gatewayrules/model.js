import { prevent } from 'util/index';
import CollectionItem from 'model/collectionItem';
import { adminPost, adminDelete } from 'util/fetch';
import { closeModal, notifyDone, confirm } from 'common/modal';

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
      adminPost({
        route_name: 'gateway_create_rule',
        mode: this.collection.filters.mode,
        body,
      })
    ).then(data => {
      if (data) {
        closeModal();
        notifyDone();
        this.collection.push(data);
        return data;
      }
    });
  }

  delete(e) {
    prevent(e);
    return confirm('Delete Rule?').then(_ => {
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
    });
  }
}
