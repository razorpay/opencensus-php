import { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import SettlementBreakupTable from 'merchant/views/Settlements/components/BreakupTable';
import { fetchBreakupDetails } from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';

@connect(state => state.settlement.breakupDetails, {
  fetchBreakupDetails,
  ...ModalActions,
})
export default class HolidayModal extends Component {
  componentWillMount() {
    this.props.fetchBreakupDetails({
      id: this.props.settlementId,
    });
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.settlementId);
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.settlementId);
  }

  render() {
    const date = new Date();
    const year = date.getUTCFullYear().toString();
    return (
      <div>
        <ModalHeader
          title={`Holidays List`}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body holiday">
          <div class="table-responsive table">
            <table class="holiday-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                {this.props.data.data[year].map((d, i) => {
                  return (
                    <tr key={i}>
                      <td class="holiday-td">{d.date}</td>
                      <td class="holiday-td">{d.description}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <div class="Modal__actions text-right">
            <button class="btn btn-default" onClick={this.props.closeModal}>
              Close
            </button>
          </div>
        </div>
      </div>
    );
  }
}
