import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { formValueSelector } from 'redux-form';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import { updateConfig } from 'merchant/reducers/config';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/reducers/payments/details';
import { closeModal } from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { bindActionCreators } from 'redux';

const selector = formValueSelector('refundModal');

const raiseTicket = () => {
  if (window.rzpTicketSystem) {
    CreateTicketEmitter.emit('create-ticket', 'tickets');

    setTimeout(() => {
      const el = document.getElementsByName('request-description')[0];
      el.value = `Hello Team,\n I’d like to know my custom pricing for instant refunds.`;
      el.focus();
    }, 1000);
  }
};

class InstantRefundFee extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  hovered = false;
  constructor(props) {
    super(props);
    this.state = {};
  }

  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Minimal Fee',
      eventLabel: `${
        this.props.pricing.custom_pricing ? 'Custom' : 'Normal'
      } Pricing | Minimal Fee`,
    });
  }

  render() {
    const rules = this.props.pricing.rules;

    return (
      <div class="instant-refund-fee-modal">
        <ModalHeader
          onCloseClick={this.props.closeModal}
          title={
            <div style={{ color: '#515978' }}>
              <i style={{ marginRight: '5px' }} class="i i-instant-refund" /> Fee for instant refund
            </div>
          }
        />
        <div class="modal-body" style={{ paddingTop: '10px', paddingBottom: '5px' }}>
          <div class="panel panel-default refund-fee-structure">
            <div class="panel-heading grey" style={{ fontWeight: 600, color: '#515978' }}>
              We charge minimal fee on each refund
            </div>
            <div class="panel-body" style={{ paddingBottom: '8px' }}>
              {!this.props.pricing.custom_pricing ? (
                <div class="instant-breakup">
                  <div class="flex">
                    <div style={{ marginBottom: '5px' }} class="w50 text-left t-heading">
                      Refund Amount
                    </div>
                    <div style={{ marginBottom: '5px' }} class="w50 text-right t-heading">
                      Processing Fees
                    </div>
                  </div>
                  {rules.map((r, i) => (
                    <div key={i} class="flex">
                      <div class="text-left amt" style={{ flexGrow: 1 }}>
                        ₹ {i > 0 ? r.amount_range_min / 100 + 1 : r.amount_range_min / 100}{' '}
                        {i == rules.length - 1 ? 'and' : '-'}{' '}
                        {i == rules.length - 1 ? `above` : r.amount_range_max / 100}{' '}
                      </div>
                      <div class="text-right" style={{ flexGrow: 1 }}>
                        <Amount
                          value={r.fixed_rate}
                          currency="INR"
                          parentQuerySelector=".Modal--small"
                        />
                      </div>
                    </div>
                  ))}{' '}
                </div>
              ) : (
                <div class="flex">
                  <div style={{ color: '#515978' }}>
                    To know your pricing, please{' '}
                    <a>
                      <strong
                        class="pointer"
                        onClick={() => {
                          window.rzpAnalytics?.({
                            eventCategory: 'Dashboard - Instant Refund',
                            eventAction: 'Contact Support',
                            eventLabel: `Custom Pricing Modal | Contact Support`,
                          });
                          raiseTicket();
                        }}
                        style={{ color: '#0B70E7' }}
                      >
                        contact support
                      </strong>
                    </a>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  const partial = selector(state, 'partial');
  const payable_amount = selector(state, 'amount');
  return {
    ...state.session,
    ...state.payment,
    user: state.session.user,
    transfers: state.payment.transfers,
    default_refund_speed: state.config.config.default_refund_speed,
    partial,
    payable_amount,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      refundPayment,
      fetchPayment,
      fetchRefunds,
      updateConfig,
      fetchTransfers,
      ...NotificationsActions,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(InstantRefundFee);
