import React, { Component } from 'react';
import Duplex from 'ui/Duplex';
import { adminFetch, adminDelete } from 'util/fetch';
import { Link } from 'react-router-dom';
import AsyncButton from 'ui/AsyncButton';
import { notifyError } from 'common/modal';

export default class GenericEntity extends Component {
  params = this.props.match.params;
  title = this.title();

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
        {actions[type] && actions[type](data)}
        <Duplex pending={!data} model={data} fields={this.fields()} />
        {data && <div class="code">{JSON.stringify(data, null, 4)}</div>}
      </div>
    );
  }

  fields() {
    let data = this.state.data;
    if (data) {
      return Object.keys(data).map(key => {
        let value = data[key];
        if (value) {
          if (typeof value === 'object') {
            value = <pre>{JSON.stringify(value)}</pre>;
          }
        }
        return item => [key, value];
      });
    }
  }
}

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
};

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
