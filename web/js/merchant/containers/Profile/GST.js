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
          {!merchant_gst.gstin && (
            <span class="pull-right">
              <a onClick={this.openAddGSTModal}>Add your GST details</a>
            </span>
          )}
        </div>
        <div class="list-group details-row-container">
          <div class="list-group-item">
            <span>GST Details</span>

            {merchant_gst.gstin ? (
              <span>{merchant_gst.gstin}</span>
            ) : (
              <span class="text-danger">Not Updated</span>
            )}
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
