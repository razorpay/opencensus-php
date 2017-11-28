import { extendObservable } from 'mobx';
import { prevent } from 'util/index';
import CollectionItem from 'model/collectionItem';
import fetch, { adminPost, adminDelete } from 'util/fetch';
import { closeModal, notifyError, notifySuccess, confirm } from 'common/modal';

const defaultProps = {
  type: '',
  method: '',
};

export default class GatewayRule extends CollectionItem {
  constructor(collection, props = defaultProps) {
    super(collection, props);
    this.bind(['save', 'delete']);
  }

  cleanRuleInfo(rule) {
    const gatewayRule = { ...rule };

    Object.keys(gatewayRule).forEach(function(key) {
      gatewayRule.issuer =
        gatewayRule.issuer === 'ALL' ? null : gatewayRule.issuer;

      // Delete null, undefined or empty string keys
      if (!gatewayRule[key]) {
        delete gatewayRule[key];
      }
    });

    if (!gatewayRule.type === 'filter' && gatewayRule.filter_type) {
      delete gatewayRule.filter_type;
    }

    if (!gatewayRule.method === 'emi' && gatewayRule.emi_duration) {
      delete gatewayRule.emi_duration;
    }

    if (!gatewayRule.method === 'emi' && gatewayRule.emi_subvention) {
      delete gatewayRule.emi_subvention;
    }

    if (!gatewayRule.type === 'sorter' && gatewayRule.load) {
      delete gatewayRule.load;
    }

    return gatewayRule;
  }

  save(body, mode) {
    if (body.iins) {
      body.iins = body.iins.split(',');
    }

    body = cleanRuleInfo(body);

    return this.request(
      adminPost({
        route_name: 'gateway_create_rule',
        mode,
        body,
      })
    )
      .then(data => {
        if (data) {
          closeModal();
          notifySuccess('Gateway rule successfully added');
          this.collection.push(data);
          return data;
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  update(ruleId, body, mode) {
    if (body.iins) {
      body.iins = body.iins.split(',');
    }

    return this.request(
      fetch({
        url: 'admin/generic',
        method: 'patch',
        params: {
          route_name: 'gateway_update_rule',
          mode: mode,
          url_params: {
            '{id}': ruleId,
          },
        },
        data: {
          body,
        },
      })
    )
      .then(data => {
        if (data) {
          extendObservable(this, data);
          closeModal();
          notifySuccess(ruleId + ' Gateway Rule updated successfully');

          return data;
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
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
