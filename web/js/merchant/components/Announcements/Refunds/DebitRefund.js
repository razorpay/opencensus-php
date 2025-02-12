import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { formValueSelector } from 'redux-form';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ModalHeader from 'common/ui/ModalHeader';
import { AmountTooltip } from 'common/ui/Amount';
import Amount from 'common/ui/Amount';
import { Fragment } from 'react';
import { updateConfig } from 'merchant/reducers/config';

import { isBlank, rupeesToPaise, paiseToRupees, titleCase } from 'common/utils/rzp-utils';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/reducers/payments/details';
import { closeModal } from 'merchant_common/reducers/modals';
import { showWhenUtil } from 'merchant/components/ShowWhen';

const selector = formValueSelector('refundModal');

class DebitRefundAnnouncement extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  hovered = false;
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
      pricing: [
        {
          name: 'Refund Amount',
          data: ['₹1 - ₹1000 ', '₹1001 - ₹25000', 'Above ₹25001'],
        },
        {
          name: 'Old Pricing',
          data: ['₹4.99', '₹9.99', '₹19.99'],
        },
        {
          name: 'New Pricing',
          data: ['₹7.99', '₹11.99', '₹14.99'],
        },
      ],
    };
  }

  componentDidMount() {}

  render() {
    return (
      <div className="debit-refund-notif">
        <Fragment>
          <div className="panel-body debit-refund-panel-body" style={{ paddingBottom: '8px' }}>
            <div className="row">
              <div className="col-xs-6">
                <h3 className="title">Instant Refunds on Debit Card payments is here!</h3>
              </div>
              <div className="col-xs-6"></div>
            </div>
            <div className="row">
              <div className="col-xs-6">
                <p>
                  We are happy to inform you that instant refunds are now supported for debit cards
                  as well.
                </p>
              </div>
            </div>
            <div className="row">
              <div className="col-xs-6">
                <h4 className="changing">What's Changing?</h4>
                <ul>
                  <li>
                    You can now process refunds instantly for payments made on - Credit Cards, Debit
                    Cards, UPI & Net Banking.
                  </li>
                  <li>
                    Instant Refunds pricing is also getting revised and the revised pricing will be
                    effective from <strong>8th Sep 2020</strong>
                  </li>
                </ul>
              </div>
            </div>
            <div className="row">
              <div className="col-xs-9">
                <div className="table">
                  <table className="table">
                    {this.state.pricing.map((p) => {
                      return (
                        <tr>
                          <td>{p.name}</td>
                          {p.data.map((i) => (
                            <td>
                              <span>{i}</span>
                            </td>
                          ))}
                        </tr>
                      );
                    })}
                  </table>
                </div>
              </div>
            </div>
            <div className="row">
              <div className="col-xs-9">
                <b>Note:</b> Your default refund speed is currently set to ‘Instant’. If you wish to
                change it to ‘Normal’, please{' '}
                <a
                  href="/app/config#instantrefunds"
                  onClick={this.props.onSuccess}
                  className="nav-link"
                >
                  Update your preferences
                </a>
                .
              </div>
            </div>
            <div className="row">
              <div className="col-xs-9">
                <button onClick={this.props.onClose} className="btn btn-primary">
                  Got It!
                </button>
              </div>
            </div>
            <div className="banner">
              <div className="svg-bg"></div>
              <div className="banner-bg"></div>
              <img src="https://razorpay.com/assets/instant-refunds/illustration.webp" alt="" />
            </div>
          </div>
        </Fragment>
      </div>
    );
  }
}

export default connect(
  (state) => {
    let partial = selector(state, 'partial');
    let reverse_all = selector(state, 'reverse_all');
    let payable_amount = selector(state, 'amount');
    return {
      ...state.session,
      ...state.payment,
      user: state.session.user,
      transfers: state.payment.transfers,
      default_refund_speed: state.config.config.default_refund_speed,
      partial,
      default_refund_speed: state.config.config.default_refund_speed,
      payable_amount,
    };
  },
  {
    closeModal,
    refundPayment,
    fetchPayment,
    fetchRefunds,
    updateConfig,
    fetchTransfers,
    ...NotificationsActions,
  },
)(DebitRefundAnnouncement);
