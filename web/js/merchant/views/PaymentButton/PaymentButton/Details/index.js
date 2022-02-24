import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';
import PropTypes from 'prop-types';

import { keysToSentence } from 'common/utils/rzp-utils';
import { updateItem } from 'common/utils/immutable';
/* global moment */

import {
  fetchPaymentPageEntity as fetchPaymentButtonEntity,
  fetchPaymentsListForPaymentPage as fetchPaymentsListForPaymentButton,
  editPaymentPage as editPaymentButton,
  editPaymentPageItem as editPaymentButtonItem,
  activatePaymentPage as activatePaymentButton,
  deactivatePaymentPage as deactivatePaymentButton,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { updatePBInReduxList } from 'merchant/reducers/paymentbuttons/list';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Spinner from 'common/ui/Spinner';
import NoEntityResultsFound from 'common/ui/NoEntityResultsFound';
import ActivateAgain from 'merchant/views/PaymentPages/PaymentPages/components/Modals/ActivateAgain';

import Details from './Details';
import track from './track';

@withRouter
@connect(null, {
  showNotification,
  closeModal,
  openModal,
  updatePBInReduxList,
})
@RTracking(() => window.rzpQ.component('PaymentButtonDetails'))
export default class PaymentButtonDetails extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    paymentButtonEntity: {},
    paymentButtonPayments: [],
    paymentsListLoading: true,
    createdByUser: null,
  };

  get entityId() {
    return this.props.id || this.props.match.params.id;
  }

  componentDidMount() {
    // @avinash Should be payment_button_id. To be same as Create/index.js
    track.init(this.props.tracking.trackEvent, this.entityId);

    track.detailsStart();
  }

  UNSAFE_componentWillMount() {
    this.fetchEntity(this.entityId);
    this.fetchEntityPayments(this.entityId);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const nextPropsEntityId = nextProps.id || nextProps.match.params.id;
    if (this.entityId !== nextPropsEntityId) {
      this.fetchEntity(nextPropsEntityId);
      this.fetchEntityPayments(nextProps.id);
    }
  }

  sanitizePaymentButtonEntity = (data) => {
    const paymentButtonEntity = data;

    const udfSchema = JSON.parse(paymentButtonEntity.settings.udf_schema);

    const formItems = [].concat(udfSchema).concat(paymentButtonEntity.payment_page_items);

    formItems.sort((a, b) => {
      const positionA = a.settings.position;
      const positionB = b.settings.position;

      return Number(positionA) - Number(positionB);
    });

    // Currently, receipt settings are mixed with settings, and in scattered form, hence consolidating
    const receiptSettings = {
      enable_receipt: paymentButtonEntity.settings.enable_receipt || '1',
      selected_udf_field: paymentButtonEntity.settings.selected_udf_field || '',
      enable_custom_serial_number: paymentButtonEntity.settings.enable_custom_serial_number || '0',
      enable_80g_details: paymentButtonEntity.settings.enable_80g_details || '0',
    };

    paymentButtonEntity.receipt = receiptSettings;

    return {
      paymentButtonEntity,
      formItems,
    };
  };

  fetchEntity(id) {
    this.setState({
      loading: true,

      createdByUser: null,
      paymentButtonEntity: {},
      paymentButtonPayments: [],
      paymentsListLoading: true,
    });

    return fetchPaymentButtonEntity(id)
      .then((resp) => {
        if (resp) {
          const { paymentButtonEntity, formItems } = this.sanitizePaymentButtonEntity(resp.data);
          this.setState({
            paymentButtonEntity,
            formItems,
            createdByUser: resp.data.user,
          });
        }

        this.setState({ loading: false });

        return resp;
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ loading: false });
      });
  }

  fetchEntityPayments(id) {
    return fetchPaymentsListForPaymentButton(id)
      .then((resp) => {
        if (resp) {
          this.setState({ paymentButtonPayments: resp.data.items });
        }

        this.setState({ paymentsListLoading: false });

        return resp;
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ paymentsListLoading: false });
      });
  }

  reActivateLink = () => {
    const statusReason = this.state.paymentButtonEntity.status_reason;

    const isExpired = statusReason.toLowerCase() === 'expired';
    const isCompleted = statusReason.toLowerCase() === 'completed';

    const currentTimeStamp = moment().unix();
    // Ideally, it should consider 2 min window, because it would take time for merchant to update.
    const reactivationTimeGap = 15 * 60;
    const hasExpiredInCompletedState =
      this.state.paymentButtonEntity.expire_by &&
      this.state.paymentButtonEntity.expire_by < currentTimeStamp + reactivationTimeGap; // within 15 minutes

    this.props.openModal({
      size: 'medium',
      component: (
        <ActivateAgain
          title="Activate Payment Button?"
          description="Once you activate the payment button, you will be able to accept payments."
          reactivationTimeGap={reactivationTimeGap}
          expireBy={
            isExpired || hasExpiredInCompletedState
              ? this.state.paymentButtonEntity.expire_by
              : undefined
          }
          isCompleted={isCompleted}
          handleClose={this.props.closeModal}
          handleClick={(data) => {
            return activatePaymentButton(this.state.paymentButtonEntity.id, data)
              .then((resp) => {
                if (resp.data) {
                  this.props.updatePBInReduxList(resp.data, false);

                  this.setState({
                    paymentButtonEntity: resp.data,
                  });

                  this.props.showNotification({
                    type: 'success',
                    message: `${this.state.paymentButtonEntity.id} is now Active`,
                  });
                } else {
                  throw new Error('Some network error has occurred');
                }

                return resp;
              })
              .catch(({ errors }) => {
                let err = errors;

                if (Array.isArray(err)) {
                  err = [];

                  if (errors.length) {
                    errors.forEach((e) => {
                      if (e && e.toLowerCase().indexOf('status code') === -1) {
                        err.push(e);
                      }
                    });
                  }

                  err = err.length ? err : null;
                }

                if (!err) {
                  err = `Some network error has occurred`;
                }

                this.props.showNotification({
                  type: 'error',
                  message: err,
                });

                throw errors;
              });
          }}
        />
      ),
    });
  };

  editPaymentButton = (data, paymentButtonItemId) => {
    const isEntityPaymentButtonItem = !!paymentButtonItemId;

    const _updateFn = isEntityPaymentButtonItem ? editPaymentButtonItem : editPaymentButton;

    const id = isEntityPaymentButtonItem ? paymentButtonItemId : this.state.paymentButtonEntity.id;

    return _updateFn(id, data)
      .then((resp) => {
        if (resp.data) {
          const keys = { ...data };

          this.props.showNotification({
            type: 'success',
            message: `${keysToSentence(keys)} updated successfully`,
          });

          let newPaymentButtonEntity;
          if (isEntityPaymentButtonItem) {
            const paymentPageItems = this.state.paymentButtonEntity.payment_page_items;
            let itemIndexInArray;

            paymentPageItems.find((pi, ix) => {
              itemIndexInArray = ix;
              return pi.id === resp.data.id;
            });

            newPaymentButtonEntity = { ...this.state.paymentButtonEntity };

            if (itemIndexInArray != null) {
              newPaymentButtonEntity.payment_page_items = updateItem(
                paymentPageItems,
                itemIndexInArray,
                resp.data,
              );
            } else {
              throw new Error('Please Reload the page'); // index must index, so this Shouldn't happen though
            }
          } else {
            const paymentButtonEntity = resp.data;

            if (data.settings) {
              newPaymentButtonEntity = {
                ...paymentButtonEntity,
                settings: {
                  ...this.state.paymentButtonEntity.settings,
                  ...data.settings,
                },
              };
            } else {
              newPaymentButtonEntity = paymentButtonEntity;
            }
          }

          this.props.updatePBInReduxList(newPaymentButtonEntity, false);
          this.setState({
            paymentButtonEntity: newPaymentButtonEntity,
          });

          return resp;
        } else {
          throw new Error('Some network issue occured');
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          if (errors.length) {
            errors.forEach((e) => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });
          }

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  updatePaymentButtonEntity = (newChanges) => {
    this.setState((prevState) => {
      return {
        paymentButtonEntity: {
          ...prevState.paymentButtonEntity,
          ...newChanges,
        },
      };
    });
  };

  toggleManualActivation = () => {
    const status = this.state.paymentButtonEntity.status;
    const statusReason = this.state.paymentButtonEntity.status_reason;

    const isActive = status === 'active';
    const isDeactivated = statusReason && statusReason.toLowerCase() === 'deactivated';

    let apiAction, header, message, affirmativeLabel, affirmativePendingLabel, successMsg;

    if (isActive) {
      /* Wants manual deactivation */
      apiAction = deactivatePaymentButton;
      header = 'Deactivate Payment Button?';
      message =
        'Once you deactivate the payment button, you will not be able to accept payments till you activate it again.';
      affirmativeLabel = 'Yes, deactivate';
      affirmativePendingLabel = 'Deactivating..';
      successMsg = `${this.state.paymentButtonEntity.id} is now Inactive`;
    } else if (isDeactivated) {
      /* Wants activation for manual deactivation for cancelled status */

      apiAction = activatePaymentButton;
      header = 'Activate Page?';
      message = 'Once you activate the page, you will be able to accept payments.';
      affirmativeLabel = 'Yes, activate';
      affirmativePendingLabel = 'Activating..';
      successMsg = `${this.state.paymentButtonEntity.id} is now Active`;
    }

    this.context.confirm({
      header,
      message: () => (
        <div class="text-semi-muted">
          <p>{message}</p>
        </div>
      ),
      affirmativeLabel,
      affirmativePendingLabel,
      abortLabel: "No, don't!",
      action: () => {
        return apiAction(this.state.paymentButtonEntity.id)
          .then((resp) => {
            if (resp.data) {
              this.props.showNotification({
                type: 'success',
                message: successMsg,
              });

              this.props.closeModal();

              this.props.updatePBInReduxList(resp.data, false);

              this.setState({
                paymentButtonEntity: resp.data,
              });
            }
            return resp;
          })
          .catch(({ errors }) => {
            let err = errors;

            if (Array.isArray(err)) {
              err = [];

              if (errors.length) {
                errors.forEach((e) => {
                  if (e && e.toLowerCase().indexOf('status code') === -1) {
                    err.push(e);
                  }
                });
              }

              err = err.length ? err : null;
            }

            if (!err) {
              err = `Some network error has occured`;
            }

            this.props.showNotification({
              type: 'error',
              message: err,
            });
          });
      },
    });
  };

  render() {
    const { paymentButtonEntity, loading } = this.state;

    if (loading) {
      return (
        <div class="content-wrapper content-sm txn-details Entity--paymentpage Entity--paymentbutton">
          <div class="page-spinner-container">
            <Spinner />
          </div>
        </div>
      );
    }

    if (!loading && !Object.keys(paymentButtonEntity).length) {
      return (
        <div class="content-wrapper content-sm txn-details Entity--paymentpage Entity--paymentbutton">
          <NoEntityResultsFound
            error={
              <span>
                No results found for id: <i>{this.entityId}</i>
              </span>
            }
          />
        </div>
      );
    }

    return (
      <Details
        {...this.props}
        {...this.state}
        editPaymentButton={this.editPaymentButton}
        fetchEntity={this.fetchEntity}
        fetchEntityPayments={this.fetchEntityPayments}
        updatePaymentButtonEntity={this.updatePaymentButtonEntity}
        reActivateLink={this.reActivateLink}
        toggleManualActivation={this.toggleManualActivation}
      />
    );
  }
}
