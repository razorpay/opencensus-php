import React from 'react';
import moment from 'moment';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import { updatePPInReduxList } from 'merchant/reducers/invoices/list';
import { keysToSentence } from 'common/utils/rzp-utils';

import {
  fetchPaymentPageEntity,
  fetchPaymentsListForPaymentPage,
  editPaymentPage,
  editPaymentPageItem,
  activatePaymentPage,
  deactivatePaymentPage,
} from '../model';
import Spinner from 'common/ui/Spinner';
import { updateItem } from 'common/utils/immutable';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackDetailViewEdits } from '../ga';
import track from './track';

import NoEntityResultsFound from 'common/ui/NoEntityResultsFound';

import PaymentPagesV3Entity from './V3';

import ActivateAgain from 'merchant/views/PaymentPages/PaymentPages/components/Modals/ActivateAgain';

@withRouter
@connect(() => ({}), {
  updatePPInReduxList,
  showNotification,
  closeModal,
  openModal,
})
@RTracking(() => window.rzpQ.component('PaymentPagesDetails'))
export default class extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    paymentPageEntity: {},
    paymentPagePayments: [],
    paymentsListLoading: true,
    createdByUser: null,
  };

  UNSAFE_componentWillMount() {
    this.fetchEntity(this.entityId);
    this.fetchEntityPayments(this.entityId);

    track.init(this.props.tracking.trackEvent);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const nextPropsEntityId = nextProps.id || nextProps.match.params.id;
    if (this.entityId !== nextPropsEntityId) {
      this.fetchEntity(nextPropsEntityId);
      this.fetchEntityPayments(nextProps.id);
    }
  }

  fetchEntity(id) {
    this.setState({
      loading: true,

      createdByUser: null,
      paymentPageEntity: {},
      paymentPagePayments: [],
      paymentsListLoading: true,
    });

    return fetchPaymentPageEntity(id)
      .then((resp) => {
        if (resp) {
          this.setState({
            paymentPageEntity: resp.data,
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
    return fetchPaymentsListForPaymentPage(id)
      .then((resp) => {
        if (resp) {
          this.setState({ paymentPagePayments: resp.data.items });
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

  editPaymentPage = (data, paymentPageItemId) => {
    const isEntityPaymentPageItem = !!paymentPageItemId;

    const _updateFn = isEntityPaymentPageItem ? editPaymentPageItem : editPaymentPage;

    const id = isEntityPaymentPageItem ? paymentPageItemId : this.state.paymentPageEntity.id;

    return _updateFn(id, data)
      .then((resp) => {
        if (resp.data) {
          const keys = { ...data };

          this.props.showNotification({
            type: 'success',
            message: `${keysToSentence(keys)} updated successfully`,
          });

          let newPaymentPageEntity;
          if (isEntityPaymentPageItem) {
            const paymentPageItems = this.state.paymentPageEntity.payment_page_items;
            let itemIndexInArray;

            paymentPageItems.find((pi, ix) => {
              itemIndexInArray = ix;
              return pi.id === resp.data.id;
            });

            newPaymentPageEntity = { ...this.state.paymentPageEntity };

            if (itemIndexInArray != null) {
              newPaymentPageEntity.payment_page_items = updateItem(
                paymentPageItems,
                itemIndexInArray,
                resp.data,
              );
            } else {
              throw new Error('Please Reload the page'); // index must index, so this Shouldn't happen though
            }
          } else {
            // maintaining settings as the api doesn't return settings
            newPaymentPageEntity = {
              settings: this.state.paymentPageEntity.settings,
              ...resp.data,
            };
          }

          this.props.updatePPInReduxList(newPaymentPageEntity, false);
          this.setState({
            paymentPageEntity: newPaymentPageEntity,
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

  /*
   * 1) Direct manual Activation can be done only when it was manually closed
   * 2) Direct manual deactivation can be done any time user wants while in Active State;
   **/
  toggleManualActivation = () => {
    const status = this.state.paymentPageEntity.status;
    const statusReason = this.state.paymentPageEntity.status_reason;

    const isActive = status === 'active';
    const isDeactivated = statusReason && statusReason.toLowerCase() === 'deactivated';

    let apiAction, header, message, affirmativeLabel, affirmativePendingLabel, successMsg;

    if (isActive) {
      /* Wants manual deactivation */
      apiAction = deactivatePaymentPage;
      header = 'Deactivate Page?';
      message =
        'Once you deactivate the page, you will not be able to accept payments till you activate it again.';
      affirmativeLabel = 'Yes, deactivate';
      affirmativePendingLabel = 'Deactivating..';
      successMsg = `${this.state.paymentPageEntity.id} is now Inactive`;
    } else if (isDeactivated) {
      /* Wants activation for manual deactivation for cancelled status */

      apiAction = activatePaymentPage;
      header = 'Activate Page?';
      message = 'Once you activate the page, you will be able to accept payments.';
      affirmativeLabel = 'Yes, activate';
      affirmativePendingLabel = 'Activating..';
      successMsg = `${this.state.paymentPageEntity.id} is now Active`;
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
        return apiAction(this.state.paymentPageEntity.id)
          .then((resp) => {
            if (resp.data) {
              // preverse missing fields from success response (like settings key)
              // eslint-disable-next-line react/no-access-state-in-setstate
              const newPaymentPageEntity = { ...this.state.paymentPageEntity, ...resp.data };
              this.props.showNotification({
                type: 'success',
                message: successMsg,
              });

              this.props.closeModal();

              this.props.updatePPInReduxList(newPaymentPageEntity, false);

              this.setState({
                paymentPageEntity: newPaymentPageEntity,
              });
              trackDetailViewEdits('Toggle Status', isActive ? 'deactivate' : 'activate');

              track.pageStatus(isActive ? 'deactivate' : 'activate');
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

  reActivateLink = () => {
    const statusReason = this.state.paymentPageEntity.status_reason;

    const isExpired = statusReason.toLowerCase() === 'expired';
    const isCompleted = statusReason.toLowerCase() === 'completed';
    const isDeactivated = statusReason.toLowerCase() === 'deactivated';

    if (isDeactivated) {
      this.toggleManualActivation();
      return;
    }

    const currentTimeStamp = moment().unix();
    // Ideally, it should consider 2 min window, because it would take time for merchant to update.
    const reactivationTimeGap = 15 * 60;
    const hasExpiredInCompletedState =
      this.state.paymentPageEntity.expire_by &&
      this.state.paymentPageEntity.expire_by < currentTimeStamp + reactivationTimeGap; // within 15 minutes

    this.props.openModal({
      size: 'medium',
      component: (
        <ActivateAgain
          reactivationTimeGap={reactivationTimeGap}
          expireBy={
            isExpired || hasExpiredInCompletedState
              ? this.state.paymentPageEntity.expire_by
              : undefined
          }
          isCompleted={isCompleted}
          handleClose={this.props.closeModal}
          handleClick={(data) => {
            return activatePaymentPage(this.state.paymentPageEntity.id, data)
              .then((resp) => {
                if (resp.data) {
                  const newPaymentPageEntity = {
                    // eslint-disable-next-line react/no-access-state-in-setstate
                    ...this.state.paymentPageEntity,
                    ...resp.data,
                  };
                  this.props.updatePPInReduxList(newPaymentPageEntity, false);

                  this.setState({
                    paymentPageEntity: newPaymentPageEntity,
                  });

                  this.props.showNotification({
                    type: 'success',
                    message: `${this.state.paymentPageEntity.id} is now Active`,
                  });
                } else {
                  throw new Error('Some network error has occured');
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

                throw errors;
              });
          }}
        />
      ),
    });
  };

  get entityId() {
    return this.props.id || this.props.match.params.id; // This view can be invoked as slider(+standalone) / only standalone view as per V2/V3
  }

  render() {
    const { paymentPageEntity, loading } = this.state;

    if (loading) {
      return (
        <div class="content-wrapper content-sm txn-details Entity--paymentpage">
          <div class="page-spinner-container">
            <Spinner />
          </div>
        </div>
      );
    }

    if (!loading && !Object.keys(paymentPageEntity).length) {
      return (
        <div class="content-wrapper content-sm txn-details Entity--paymentpage">
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
      <PaymentPagesV3Entity
        {...this.props}
        {...this.state}
        fetchEntity={this.fetchEntity}
        fetchEntityPayments={this.fetchEntityPayments}
        editPaymentPage={this.editPaymentPage}
        toggleManualActivation={this.toggleManualActivation}
        reActivateLink={this.reActivateLink}
      />
    );
  }
}
