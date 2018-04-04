import { Component } from 'react';
import { connect } from 'react-redux';
import { fetchGST } from 'merchant/modules/profile';
import { openModal } from 'rzp/modules/modals';
import AddGST from './AddGST';

@connect(state => state.profile, { fetchGST, openModal })
export default class GSTDetails extends Component {
  componentWillMount() {
    this.props.fetchGST();
  }

  openAddGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AddGST />,
    });
  };

  render() {
    let { merchant_gst, rzp_gst } = this.props;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          GST Details
          <span class="pull-right">
            {do {
              if (!merchant_gst.gstin && !merchant_gst.p_gstin) {
                <a onClick={this.openAddGSTModal}>Add your GST details</a>;
              } else if (!merchant_gst.gstin && merchant_gst.p_gstin) {
                <a onClick={this.openAddGSTModal}>Update GST details</a>;
              }
            }}
          </span>
        </div>
        <div class="list-group details-row-container">
          <div class="list-group-item">
            <span>GST Details</span>

            {do {
              if (!merchant_gst.gstin && !merchant_gst.p_gstin) {
                <span class="text-danger">Not Updated</span>;
              } else if (!merchant_gst.gstin && merchant_gst.p_gstin) {
                <span>{merchant_gst.p_gstin} (Provisional)</span>;
              } else {
                <span>{merchant_gst.gstin}</span>;
              }
            }}
          </div>

          <div class="list-group-item">
            <span>Razorpay's GST Number</span>
            <span>{rzp_gst.gstin}</span>
          </div>
        </div>
      </div>
    );
  }
}
