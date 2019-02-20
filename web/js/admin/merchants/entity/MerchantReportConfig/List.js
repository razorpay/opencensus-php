import { Component } from 'react';

import { notifySuccess, openModal, closeModal } from 'common/modal';
import { adminFetch, adminDelete } from 'common/fetch';

import { ModalContent } from 'component/Modal';
import { PageTable } from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

import Collection from 'model/collection';

import { pickProps } from 'rzp/utils/rzp-utils';

import CreateEntity from './Create';
import Entity from './Entity';

const getFields = ({ view, edit, clone, remove, merchantId }) => [
  [
    'ID',
    item => (
      <span class="link" onClick={view(item)}>
        {item.id}
      </span>
    ),
  ],
  ['Name', item => item.name],
  ['Description', item => item.description],
  ['No. Of Columns', item => (item.template.output_fields || []).length],
  [
    '',
    item => (
      <span class="link" onClick={clone(item)}>
        Clone
      </span>
    ),
  ],
  [
    '',
    item =>
      item.consumer === merchantId && (
        <span class="link" onClick={edit(item)}>
          Edit
        </span>
      ),
  ],
  [
    '',
    item =>
      item.consumer === merchantId && (
        <span class="link danger" onClick={remove(item)}>
          Delete
        </span>
      ),
  ],
];

export default class MerchantReportConfigList extends Component {
  componentWillMount() {
    this.collection = new Collection({
      fetchFn: fetchConfigList.bind(this, this.props.match.params.id),
    });
  }

  view = item => () => {
    openModal(
      <div style={{ width: '1050px' }}>
        <ModalContent header="Report Config">
          <Entity config={item} />
        </ModalContent>
      </div>
    );
  };

  openWriteConfigModal = ({ values = {}, mode, onSave }) => {
    openModal(
      <div style={{ width: '1050px' }}>
        <ModalContent header="Create New Report Config">
          <CreateEntity
            merchantId={this.props.match.params.id}
            configId={mode === 'edit' ? values.id : undefined}
            onSave={onSave}
            values={pickProps(values, [
              'description',
              'emails',
              'name',
              'scheduled',
              'template',
              'type',
            ])}
          />
        </ModalContent>
      </div>
    );
  };

  addNew = () => {
    this.openWriteConfigModal({
      mode: 'new',
      onSave: config => {
        this.collection.push(config);
      },
    });
  };

  edit = config => () => {
    this.openWriteConfigModal({
      mode: 'edit',
      values: config,
      onSave: config => {
        this.collection.update(config);
      },
    });
  };

  clone = config => () => {
    this.openWriteConfigModal({
      mode: 'clone',
      values: config,
      onSave: config => {
        this.collection.push(config);
      },
    });
  };

  remove = config => () => {
    openModal(
      <ModalContent header="Delete Report Config">
        <div>
          Are you sure you want to delete report config:{' '}
          <strong>{config.name}</strong>
        </div>
        <div>
          <div class="pull-right">
            <button class="btn-reject" onClick={closeModal}>
              Cancel
            </button>
            <AsyncButton
              text="Yes"
              class="btn"
              pendingClass="small spinner"
              onClick={() =>
                adminDelete(
                  `live_${this.props.match.params.id}/reporting/configs/${
                    config.id
                  }`
                ).then(response => {
                  if (response) {
                    this.collection.remove(config);
                    closeModal();
                    notifySuccess('Report config delted successfully');
                  }
                })
              }
            />
          </div>
        </div>
      </ModalContent>
    );
  };

  render() {
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Merchant Report Configs
            <button class="button pull-right" onClick={this.addNew}>
              Add New
            </button>
          </header>
        </div>
        <PageTable
          model={this.collection}
          animateRow={false}
          fields={getFields({
            view: this.view,
            clone: this.clone,
            edit: this.edit,
            remove: this.remove,
            merchantId: this.props.match.params.id,
          })}
        />
      </div>
    );
  }
}

function fetchConfigList(merchantId) {
  return adminFetch(`live_${merchantId}/reporting/configs`).then(response => {
    if (response) {
      return response;
    }
  });
}
