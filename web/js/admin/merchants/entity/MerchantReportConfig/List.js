import { Component } from 'react';

import { openModal } from 'common/modal';
import { ModalContent } from 'component/Modal';

import CreateEntity from './Create';

export default class MerchantReportConfigList extends Component {
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
      <>
        <h1>Merchant Report Configs</h1>

        <button class="button" onClick={this.addNew}>
          Add New
        </button>
      </>
    );
  }
}
