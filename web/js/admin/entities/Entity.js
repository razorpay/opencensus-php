import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import Duplex from 'ui/Duplex';
import fetch, { adminFetch, adminDelete, adminPost } from 'common/fetch';
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
  inAnymode = 2; // Flag to check if entity id is not found in any mode

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
    loading: true,
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
        url: `/admin/api/${mode}/admin/${type}/${id}`,
      },
      suppressDefaultError
    ).then(data => {
      if (!data.errors && data) {
        if (data.mode) {
          data[`${type} mode`] = data.mode;
        }
        data.mode = mode;

        // Need to set because of actions on entity page need correct mode
        if (!this.params.mode) {
          this.params.mode = data.mode;
        }

        this.setState({ data, loading: false, title: this.getTitle(mode) });
      } else {
        this.inAnymode--;
        if (this.inAnymode === 0) {
          // If entity id is not found in any modes
          this.setState({ loading: false, title: 'Entity Not Found:' });
        }
      }

      return data;
    });
  }

  render() {
    let { id, type, mode = null } = this.params;
    let { data, loading } = this.state;

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
          <Duplex pending={loading} model={data} fields={this.fields()} />
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
          {data &&
            !loading && (
              <ToggleEntityRow label="Raw Data">
                <div class="code">{JSON.stringify(data, null, 4)}</div>
              </ToggleEntityRow>
            )}
        </main>
        <aside class="container">
          {data &&
            !loading &&
            actions[type] && (
              <div class="header">
                <b>ACTIONS</b>
              </div>
            )}
          {data && !loading && actions[type] && actions[type](data, this)}
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
          value = <pre class="duplex-json">{JSON.stringify(value)}</pre>;
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
  payment: [item => ['verified', verifyStatus[item.verified] || 'UNKNOWN']],
};

const verifyStatus = {
  0: <span class="text-danger text-right">FAILED</span>,
  1: <span class="text-success text-right">SUCCESS</span>,
  2: <span class="text-danger">ERROR</span>,
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

  refund: (entity, entityComponent) => (
    <action.RefundActions entity={entity} mode={entityComponent.params.mode} />
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
  batch: (entity, entityComponent) =>
    entity &&
    entity.status !== 'processed' &&
    !entity.processing && (
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
  credits: (entity, entityComponent) => (
    <action.CreditActions
      entity={entity}
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
  return adminDelete(`emi/${this.id}`);
}

function downloadFile() {
  var windowRef = window.open('', '_blank');
  adminFetch(`${this.mode}/files/${this.id}/signed-url`).then(data => {
    if (data) {
      windowRef.location.href = data.url;
    } else {
      windowRef.close();
      notifyError(data.errors.join(', '));
    }
  });
}

function retryBatch(updateEntity) {
  return adminPost(`${this.mode}/batches/${this.id}/process`).then(response => {
    if (response) {
      updateEntity();
      notifySuccess(`Batch: ${response.id} retried successfully.`);
    }
  });
}
