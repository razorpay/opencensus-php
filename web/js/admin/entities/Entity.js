import React, { Component, Fragment } from 'react';
import Duplex from 'ui/Duplex';
import { adminFetch, adminDelete, adminPost } from 'util/fetch';
import { Link } from 'react-router-dom';
import AsyncButton from 'ui/AsyncButton';
import { notifyDone, notifyError } from 'common/modal';
import user from 'admin/user';

import * as action from './entityActions/index';

export default class GenericEntity extends Component {
  params = this.props.match.params;
  title = this.title();
  fields = this::getFields;

  title() {
    let type = this.params.type.replace('_', ' ');
    if (this.params.mode) {
      type = this.params.mode + ' ' + type;
    }
    return type;
  }

  state = {
    data: null,
  };

  componentWillMount() {
    let { type, mode, id } = this.params;

    adminFetch({
      mode,
      route_name: 'admin_fetch_entity_by_id',
      url_params: {
        id,
        type: type,
      },
    }).then(data => {
      if (data) {
        this.setState({ data });
        return data;
      }
    });
  }

  render() {
    let { id, type } = this.params;
    let { data } = this.state;

    return (
      <div class="box limited">
        {data &&
          data.merchant_id && (
            <Link to={'/merchants/' + data.merchant_id}>
              <i class="box-icon i-user-circle"> {data.merchant_id}</i>
            </Link>
          )}
        <header class="capitalize">
          {this.title} <code>{id}</code>
        </header>
        <Duplex pending={!data} model={data} fields={this.fields()} />
        {data && <div class="code">{JSON.stringify(data, null, 4)}</div>}
        <div class="separate" style={{ padding: '10px' }}>
          {data && actions[type] && actions[type](data, this)}
        </div>
      </div>
    );
  }
}

export function getFields() {
  let data = this.state.data;
  if (data) {
    let fields = Object.keys(data).map(key => {
      let value = data[key];
      if (value) {
        if (typeof value === 'object') {
          value = <pre>{JSON.stringify(value)}</pre>;
        }
      }
      return item => [key, value];
    });
    let moreFields = extraFields[data.entity];
    if (moreFields) {
      fields = moreFields.concat(fields);
    }
    return fields;
  }
}

const extraFields = {
  payment: [item => ['verified', verifyStatus[item.verified] || '?']],
};

const verifyStatus = {
  1: <i class="i-yes" />,
  0: <i class="i-yes" />,
  2: 'Verify Error',
};

const actions = {
  emi_plan: entity => (
    <AsyncButton
      class="btn danger"
      pendingClass="small spinner"
      onClick={entity::deleteEmiPlan}
      text="Delete EMI Plan"
      confirm="Delete EMI Plan?"
    />
  ),

  file_store: entity => (
    <button class="btn" onClick={entity::downloadFile}>
      Download
    </button>
  ),

  payment: (entity, entityComponent) => (
    <action.PaymentActions
      entity={entity}
      mode={entityComponent.params.mode}
      updateEntity={entityComponent::updateEntity}
    />
  ),

  offer: (entity, entityComponent) => (
    <action.OfferActions
      entity={entity}
      mode={entityComponent.params.mode}
      updateEntity={entityComponent::updateEntity}
    />
  ),

  terminal: (entity, entityComponent) => (
    <action.TerminalActions
      entity={entity}
      mode={entityComponent.params.mode}
      updateEntity={entityComponent::updateEntity}
    />
  ),

  dispute: (entity, entityComponent) => (
    <action.DisputeActions
      entity={entity}
      mode={entityComponent.params.mode}
      updateEntity={entityComponent::updateEntity}
    />
  ),

  iin: (entity, entityComponent) => (
    <action.IINActions
      entity={entity}
      mode={entityComponent.params.mode}
      updateEntity={entityComponent::updateEntity}
    />
  ),
};

function updateEntity(data) {
  this.setState({
    data: { ...this.state.data, ...data },
  });
}

function deleteEmiPlan() {
  return adminDelete({
    route_name: 'emi_plan_delete',
    url_params: {
      id: this.id,
    },
  });
}

function downloadFile() {
  var windowRef = window.open('', '_blank');
  adminFetch({
    route_name: 'admin_get_file',
    url_params: {
      fileId: this.id,
    },
  }).then(data => {
    if (data.success) {
      windowRef.location.href = data.data.url;
    } else {
      windowRef.close();
      notifyError(data.errors.join(', '));
    }
  });
}
