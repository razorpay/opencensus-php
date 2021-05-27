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
import { CreateTicketEmitter } from '../../../TicketSupport/utils';
const selector = formValueSelector('refundModal');

@connect(
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
)
export default class InstantRefundPricingTable extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  hovered = false;
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentDidMount() {}

  render() {
    let rules;
    rules = this.props.pricing.rules;
    return (
      <div class="instant-refund-fee-modal">
        <Fragment>
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
                          currency={'INR'}
                          parentQuerySelector={`.Modal--small`}
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
                          window.rzpAnalytics({
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
        </Fragment>
      </div>
    );
  }
}

const raiseTicket = () => {
  if (window.rzpTicketSystem) {
    const rzpTicketSystem = window.rzpTicketSystem;
    CreateTicketEmitter.emit(
      'create-ticket',
      'ticket',
      () => {
        rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
      },
      () => {
        setTimeout(() => {
          rzpTicketSystem.modal.next();
        }, 0);
      },
    );

    setTimeout(() => {
      var el = document.getElementsByName('request-description')[0];
      el.value = 'Hello Team,\n' + 'I’d like to know my custom pricing for instant refunds.';
      el.focus();
    }, 1000);
  }
};
