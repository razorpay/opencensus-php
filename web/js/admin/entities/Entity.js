import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import Duplex from 'ui/Duplex';
import fetch, { adminFetch, adminDelete, adminPost } from 'util/fetch';
import { Link } from 'react-router-dom';
import AsyncButton from 'ui/AsyncButton';
import { notifyDone, notifyError, notifySuccess } from 'common/modal';
import ShowWhen from 'admin/components/ShowWhen';

import { PaymentRefundsList } from './entityActions/payment';
import * as action from './entityActions/index';
import ToggleEntityRow from 'ui/ToggleEntityRow';

@withRouter
export default class GenericEntity extends Component {
  params = this.props.match.params;
  fields = this::getFields;

  getTitle(mode) {
    let type = this.params.type.replace('_', ' ');
    mode = mode || this.params.mode;
    if (mode) {
      type = mode + ' ' + type;
    }
    return type;
  }

  state = {
    data: null,
    title: this.getTitle(),
  };

  componentWillMount() {
    if (!this.params.mode) {
      this.fetchEntity('live', true);
      this.fetchEntity('test', true);
    } else {
      this.fetchEntity(this.params.mode);
    }
  }

  fetchEntity(mode, suppressDefaultError) {
    let { type, id } = this.params;

    fetch(
      {
        url: '/admin/generic',
        params: {
          mode,
          route_name: 'admin_fetch_entity_by_id',
          url_params: {
            '{id}': id,
            '{type}': type,
          },
        },
      },
      suppressDefaultError
    ).then(data => {
      if (!data.errors && data) {
        if (data.mode) {
          data[`${type} mode`] = data.mode;
        }
        data.mode = mode;
        this.setState({ data, title: this.getTitle(mode) });
      }

      return data;
    });
  }

  render() {
    let { id, type, mode = null } = this.params;
    let { data } = this.state;

    return (
      <div class="entity-page">
        <main class="box limited">
          {data &&
            data.merchant_id && (
              <Link to={'/merchants/' + data.merchant_id}>
                <i class="box-icon i-user-circle"> {data.merchant_id}</i>
              </Link>
            )}
          <header>
            <span class="capitalize">{this.state.title}</span>
            <code>{id}</code>
          </header>
          <Duplex pending={!data} model={data} fields={this.fields()} />
          {type === 'payment' &&
            data && (
              <ToggleEntityRow label="Refunds">
                <PaymentRefundsList
                  id={data.id}
                  merchant_id={data.merchant_id}
                  mode={data.mode}
                />
              </ToggleEntityRow>
            )}

          <br />
          {data && (
            <ToggleEntityRow label="Raw Data">
              <div class="code">{JSON.stringify(data, null, 4)}</div>
            </ToggleEntityRow>
          )}
        </main>
        <aside class="container">
          {data &&
            actions[type] && (
              <div class="header">
                <b>ACTIONS</b>
              </div>
            )}
          {data && actions[type] && actions[type](data, this)}
        </aside>
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
  batch: (entity, entityComponent) => (
    <ShowWhen permission="retry_batch">
      <AsyncButton
        class="btn"
        pendingClass="small spinner"
        onClick={retryBatch.bind(entity, entityComponent::updateEntity)}
        text="Retry batch"
        confirm="Confirm retry batch?"
      />
    </ShowWhen>
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
    mode: this.mode,
  }).then(data => {
    if (data) {
      windowRef.location.href = data.url;
    } else {
      windowRef.close();
      notifyError(data.errors.join(', '));
    }
  });
}

function retryBatch(updateEntity) {
  const params = {
    route_name: 'batch_process_by_id',
    url_params: {
      id: this.id,
    },
    mode: this.mode,
  };

  return adminPost(params).then(response => {
    if (response) {
      updateEntity();
      notifySuccess(`Batch: ${response.id} retried successfully.`);
    }
  });
}
