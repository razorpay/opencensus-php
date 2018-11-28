import { Component } from 'react';

import { openModal } from 'common/modal';
import { adminFetch } from 'common/fetch';

import { ModalContent } from 'component/Modal';
import { PageTable } from 'ui/Table';

import Collection from 'model/collection';

import CreateEntity from './Create';
import Entity from './Entity';

const getFields = ({ handleViewClick }) => [
  ['ID', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
  ['No. Of Columns', item => item.template.output_fields.length],
  [
    'Actions',
    item => (
      <>
        <button class="btn" onClick={handleViewClick(item)}>
          View
        </button>
      </>
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
      <div style={{ width: '750px' }}>
        <ModalContent header="Report Config">
          <Entity config={item} />
        </ModalContent>
      </div>
    );
  };

  addNew = () => {
    openModal(
      <div style={{ width: '750px' }}>
        <ModalContent header="Create New Report Config">
          <CreateEntity merchantId={this.props.match.params.id} />
        </ModalContent>
      </div>
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
          fields={getFields({ handleViewClick: this.handleViewClick })}
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
