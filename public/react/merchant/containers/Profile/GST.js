import { Component } from 'react';
import { connect } from 'react-redux';
import { fetchGST, saveGST } from 'merchant/modules/profile';
import { openModal } from 'rzp/modules/modals';
import AddGST from './AddGST';

@connect(state => state.profile, { fetchGST, saveGST, openModal })
export default class GSTDetails extends Component {
  componentWillMount() {
    this.props.fetchGST();
  }

  openAddGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AddGST merchant_gst={this.props.merchant_gst} />,
    });
  };

  render() {
    let { merchant_gst, rzp_gst } = this.props;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">GST Details</div>
        <div class="list-group details-row-container">
          <div class="list-group-item">
            <span>
              GST Details {' '}
              {!merchant_gst.gstin && merchant_gst.p_gstin
                ? <span class="text-danger">(Provisional)</span>
                : null}
            </span>

            {
              do {
                if (merchant_gst.gstin) {
                  <span>{merchant_gst.gstin}</span>;
                } else if (merchant_gst.p_gstin) {
                  <span>
                    {merchant_gst.p_gstin}
                    <a
                      onClick={this.openAddGSTModal}
                      style={{ marginLeft: '5px' }}
                    >
                      Update GST
                    </a>
                  </span>;
                } else {
                  <a onClick={this.openAddGSTModal}>Add your GST details</a>;
                }
              }
            }
          </div>

          <div class="list-group-item">
            <span>Razorpay's GST Number (Provisional)</span>
            <span>{rzp_gst.p_gstin}</span>
          </div>
        </div>
      </div>
    );
  }
}
