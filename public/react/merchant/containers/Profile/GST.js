import { Component } from 'react';
import { connect } from 'react-redux';
import DetailRow from 'merchant/components/DetailRow';
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
      <div>
        <DetailRow
          label={() => {
            return (
              <span>
                GST Details {' '}
                {!merchant_gst.gstin && merchant_gst.p_gstin
                  ? <span class="text-danger">(Provisional)</span>
                  : null}
              </span>
            );
          }}
          value={() => {
            if (merchant_gst.gstin) {
              return merchant_gst.gstin;
            }

            if (merchant_gst.p_gstin) {
              return (
                <span>
                  {merchant_gst.p_gstin}
                  <a
                    onClick={this.openAddGSTModal}
                    style={{ marginLeft: '5px' }}
                  >
                    Update GST
                  </a>
                </span>
              );
            }
            return <a onClick={this.openAddGSTModal}>Add your GST details</a>;
          }}
        />

        <DetailRow
          label="Razorpay's GST Number (Provisional)"
          value={() => <span>{rzp_gst.p_gstin}</span>}
        />
      </div>
    );
  }
}
