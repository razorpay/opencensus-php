import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { Link } from 'react-router-dom';
import AutoResizeTextarea from 'common/ui/Forms/AutoResizeTextarea';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import { AmountTooltip } from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import { Fragment } from 'react';
import { updateConfig } from 'merchant/reducers/config';

import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
} from 'common/utils/rzp-utils';
import {
  refundPayment,
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
} from 'merchant/reducers/payments/details';
import { closeModal } from 'merchant_common/reducers/modals';
import { showWhenUtil } from 'merchant/components/ShowWhen';
const selector = formValueSelector('refundModal');

@connect(
  state => {
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
  }
)
export default class EnableInstantRefundsModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  hovered = false;
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
      instantChecked: this.props.default_refund_speed === 'optimum',
      instant_fee: {},
    };
  }

  enableInstantRefunds = () => {
    let label;
    if (this.hovered) {
      label = `${this.props.openedFrom} | Hover on Pricing | Yes Enable`;
    } else {
      label = `${this.props.openedFrom} | Didn't hover on Pricing | Yes Enable`;
    }
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Yes Enable',
      eventLabel: label,
    });
    this.props
      .updateConfig({
        default_refund_speed: 'optimum',
      })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Instant Refunds Activated Successfully',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    return (
      <div>
        <ModalHeader title="Are you sure you want to enable Instant Refund?" />
        <div class="modal-body" style={{ paddingBottom: 0 }}>
          <div>
            <React.Fragment>
              <div
                style={{ margin: '10px 0' }}
                class="change-default-refund-speed"
              >
                <div>
                  You payment will be refunded instantly at a minimal fee.
                  &nbsp;
                  {!showWhenUtil({
                    featureEnabled: 'card_transfer_refund',
                  }) ? (
                    <span>
                      <i class="i i-info-circle" />
                      <Popover
                        onMouseOver={() => (this.hovered = true)}
                        theme="dark"
                        align="bottom"
                        parentQuerySelector={`.Modal--small`}
                      >
                        <PopoverBody>
                          <div class="instant-breakup">
                            <div class="flex">
                              <div class="w50 text-left">Refund Amount</div>
                              <div class="w50 text-right">Fee Amount</div>
                            </div>
                            <hr
                              style={{
                                margin: 0,
                                marginBottom: '5px',
                                marginTop: '5px',
                              }}
                            />
                            <div class="flex">
                              <div class="w50 text-left">1-1000 INR</div>
                              <div class="w50 text-right">
                                <Amount value={499} currency={'INR'} />
                              </div>
                            </div>
                            <div class="flex">
                              <div class="w50 text-left">1001-25000 INR</div>
                              <div class="w50 text-right">
                                <Amount value={999} currency={'INR'} />
                              </div>
                            </div>
                            <div class="flex">
                              <div style={{ width: '60%' }} class="text-left">
                                25001 and above (INR)
                              </div>
                              <div style={{ width: '40%' }} class="text-right">
                                <Amount value={1999} currency={'INR'} />
                              </div>
                            </div>
                          </div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  ) : null}
                </div>
              </div>
            </React.Fragment>
          </div>
          <div class="confirm-note">
            <div>
              In settings page you will have option to switch to normal refund
            </div>
          </div>
        </div>
        <div>
          <div
            class="Modal__actions"
            style={{ padding: '20px', paddingTop: 0 }}
          >
            <div class="row flex">
              <div class="w50" style={{ margin: '0 5px' }}>
                <button
                  class="btn btn-default btn-block"
                  onClick={this.props.closeModal}
                >
                  No Don't!
                </button>
              </div>
              <div class="w50" style={{ margin: '0 5px' }}>
                <button
                  class="btn btn-primary btn-block"
                  onClick={this.enableInstantRefunds}
                >
                  Yes, Enable
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
