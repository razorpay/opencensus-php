import { Component } from 'react';

import { notifySuccess, openModal, closeModal } from 'common/modal';
import { adminFetch, adminDelete } from 'common/fetch';

import { ModalContent } from 'component/Modal';
import { PageTable } from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

import Collection from 'model/collection';

import CreateEntity from './Create';
import Entity from './Entity';

const getFields = ({ handleViewClick, remove }) => [
  ['ID', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
  ['No. Of Columns', item => item.template.output_fields.length],
  [
    'Actions',
    item => (
      <button class="btn" onClick={handleViewClick(item)}>
        View
      </button>
    ),
  ],
  [
    '',
    item => (
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

  handleViewClick = item => () => {
    openModal(
      <div style={{ width: '1050px' }}>
        <ModalContent header="Report Config">
          <Entity config={item} />
        </ModalContent>
      </div>
    );
  };

  addNew = () => {
    openModal(
      <div style={{ width: '1050px' }}>
        <ModalContent header="Create New Report Config">
          <CreateEntity merchantId={this.props.match.params.id} />
        </ModalContent>
      </div>
    );
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
            handleViewClick: this.handleViewClick,
            remove: this.remove,
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
