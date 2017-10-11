import { Component, PropTypes } from 'react';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import Amount from 'rzp/ui/Amount';
import Alert from 'rzp/ui/Forms/Alert';
import DatePickerField from 'rzp/ui/Forms/DatePickerField';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import TypeAhead from 'rzp/ui/Select/TypeAhead';
import Spinner from 'rzp/ui/Spinner';
import InlineField from 'rzp/ui/Forms/InlineField';
import { findBy } from 'rzp/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';

import LineItemTable from './LineItemTable';
import CustomerCreation from 'merchant/containers/Customers/New';
import IssueConfirmModal from './IssueConfirmModal';
import AddInternalNoteModal from './AddInternalNoteModal';
import InvoiceBreadcrumbNav from 'merchant/components/Invoices/InvoiceBreadcrumbNav';
import InvoiceInfo from 'merchant/components/Invoices/InvoiceInfo';
import InvoiceNotes from 'merchant/components/Invoices/InvoiceNotes';
import InvoiceLogo from 'merchant/components/Invoices/InvoiceLogo';
import { fetchConfig } from 'merchant/modules/config';
import { fetchCustomersForAutocomplete } from 'merchant/modules/customers';
import { fetchItemsForAutocomplete } from 'merchant/modules/items';
import { saveInvoice, deleteInvoice } from 'merchant/modules/invoices/list';
import * as InvoiceActions from 'merchant/modules/invoices/details';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

function validate(values) {
  let errors = {
    line_items: [],
  };
  let lineItems = values.line_items.filter(
    item =>
      !!((item.item_id && item.item_id !== 'NULL') || item.id || item.name)
  );

  if (!values.customer_id) {
    errors.customer_id = 'Please select a customer';
  }

  if (!lineItems.length) {
    errors.line_items[0] = {
      item_id: 'Please select an item',
    };
  } else {
    lineItems.forEach((item, index) => {
      if (Number(item.quantity) < 1) {
        errors.line_items[index] = {
          quantity: 'Quantity should be greater than zero',
        };
      }
    });
  }
  return errors;
}

const selector = formValueSelector('newInvoice');

@withRouter
@connect(
  state => {
    let customers = state.customers.items;
    return {
      session: state.session,
      customers,
      items: state.items.items,
      customer: findBy(customers, 'id', selector(state, 'customer_id')),
      invoice: state.invoice.invoice,
      invoice_line_items: selector(state, 'line_items'),
    };
  },
  {
    fetchCustomersForAutocomplete,
    fetchItemsForAutocomplete,
    saveInvoice,
    deleteInvoice,
    fetchConfig,
    ...InvoiceActions,
    ...ModalActions,
    ...NotificationsActions,
  }
)
@reduxForm({
  form: 'newInvoice',
  enableReInitialize: true,
  validate,
  initialValues: {
    date: Math.ceil(new Date().getTime() / 1000),
    draft: 0,
    type: 'invoice',
    line_items: [
      {
        quantity: 1,
        amountInINR: '0.00',
      },
    ],
  },
})
export default class InvoicesNewContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super(...arguments);
    this.state = {
      status: {},
    };
  }

  isPaymentLink(invoice) {
    if (invoice.type !== 'link') {
      return;
    }

    this.props.history.replace('/paymentlinks');
    this.props.history.replace(`/paymentlinks/${invoice.id}`);
    return true;
  }

  componentWillMount() {
    this.getMerchantInfo();
    let promises = [];

    let invoiceId = this.props.match.params.id;

    if (invoiceId) {
      promises.push(
        this.props.fetchInvoice(invoiceId).then(invoice => {
          if (this.isPaymentLink(invoice)) {
            return;
          }

          if (invoice.partial_payment) {
            this.props.fetchInvoicePayments(invoiceId);
          }
          return invoice;
        })
      );
    } else {
      this.props.initializeInvoice();
    }

    promises = [
      this.props.fetchCustomersForAutocomplete(),
      this.props.fetchItemsForAutocomplete({ type: 'invoice' }),
      ...promises,
    ];

    this.setState({
      isLoading: true,
    });

    Promise.all(promises)
      .then(([customers, items, invoice]) => {
        if (invoice) {
          this.props.initialize(invoice);
        }
        this.setState({
          isLoading: false,
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });

    this.handleWindowClose();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.match.params.id !== nextProps.match.params.id) {
      this.setState({
        isLoading: true,
      });

      this.props
        .fetchInvoice(nextProps.match.params.id)
        .then(invoice => {
          this.setState({
            isLoading: false,
          });

          if (this.isPaymentLink(invoice)) {
            return;
          }
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    }
  }

  getMerchantInfo() {
    return this.props.fetchConfig().then(response => {
      let config = response.data;
      let user = this.props.session.user;
      let merchant = user.merchants[user.current];
      let logoUrl = config.logo_url;

      this.setState({
        merchantLogoUrl: logoUrl,
        merchantAltBillingLabel: merchant.billing_label || merchant.name,
      });
    });
  }

  calculateItemsSubTotal() {
    let lineItems = this.props.invoice_line_items || [];
    return lineItems
      .reduce((total, line_item) => {
        return (
          total + Number(line_item.quantity) * Number(line_item.amountInINR)
        );
      }, 0)
      .toFixed(2);
  }

  calculateInvoiceTotal() {
    return this.calculateItemsSubTotal();
  }

  selectCustomerAndCloseModal = customer => {
    this.props.change('customer_id', customer.id);
    this.props.closeModal();
  };

  quickCreateCustomer = ({ searchTerm = '' }) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Create and add this customer"
          onSave={this.selectCustomerAndCloseModal}
          customer={{
            name: searchTerm,
          }}
        />
      ),
    });
  };

  _save(props) {
    this.setState({
      isSaving: true,
    });
    return this.props
      .saveInvoice(props)
      .then(invoice => {
        this.setState({
          isSaving: false,
        });
        this.props.initialize(invoice);
        return invoice;
      })
      .catch(error => {
        this.props.showNotification({
          type: 'error',
          message: error.errors,
        });
        this.setState({
          isSaving: false,
        });
        throw error;
      });
  }

  save = props => {
    return this._save(props).then(invoice => {
      this.props.showNotification({
        type: 'success',
        message: 'Invoice Saved',
      });
      this.props.history.push(`/invoices/${invoice.id}`);
      return invoice;
    });
  };

  saveAndIssue(props) {
    return this.showIssueConfirmModal(notifyProps => {
      return this._save({
        ...props,
        ...notifyProps,
      }).then(invoice => {
        this.props.showNotification({
          type: 'success',
          message: 'Invoice Issued',
        });
        this.props.history.push(`/invoices/${invoice.id}`);
        return invoice;
      });
    });
  }

  resendInvoice = props => {
    this.showIssueConfirmModal(notifyProps => {
      let promises = [];

      if (notifyProps.email_notify) {
        promises.push(this.props.notifyCustomer(props, 'email'));
      }
      if (notifyProps.sms_notify) {
        promises.push(this.props.notifyCustomer(props, 'sms'));
      }

      return Promise.all(promises)
        .then(([emailStatus, smsStatus]) => {
          this.props.showNotification({
            type: 'success',
            message: 'Invoice has been sent successfully!',
          });
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    }, true);
  };

  showIssueConfirmModal(onIssueCallback, disableIssueOnEmptySelection) {
    this.props.openModal({
      size: 'small',
      component: (
        <IssueConfirmModal
          disableIssueOnEmptySelection={disableIssueOnEmptySelection}
          customer={this.props.customer}
          onIssue={notifyProps => {
            return onIssueCallback(notifyProps);
          }}
        />
      ),
    });
  }

  navigateToList() {
    this.props.history.push('/invoices');
  }

  deleteInvoice = () => {
    if (!this.props.dirty) {
      return this.navigateToList();
    }

    let invoice = this.props.invoice;
    this.context.confirm({
      header: 'Delete Invoice?',
      message: () =>
        <div class="text-semi-muted">
          <p>
            The Invoice will be deleted. There is no coming back!. Are you sure?
          </p>
          <div>
            If you have added any item or customer, you can still use them in
            other invoices.
          </div>
        </div>,
      affirmativeLabel: 'Yes, Delete',
      affirmativePendingLabel: 'Deleting...',
      abortLabel: "No, don't!",
      action: () => {
        return invoice.id
          ? this.props
              .deleteInvoice(invoice)
              .then(() => {
                this.props.showNotification({
                  type: 'success',
                  message: 'Invoice deleted successfully',
                });
                this.navigateToList();
              })
              .catch(({ errors }) => {
                this.props.showNotification({
                  type: 'error',
                  message: errors,
                });
              })
          : this.navigateToList();
      },
    });
  };

  cancelInvoice = () => {
    let invoice = this.props.invoice;
    this.context.confirm({
      header: 'Cancel Invoice?',
      message: () =>
        <div class="text-semi-muted">
          <p>
            The Invoice will be cancelled and the customer will not be able to
            pay for it.
          </p>
        </div>,
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .cancelInvoice(invoice)
          .then(invoice => {
            this.props.initialize(invoice);
            this.props.showNotification({
              type: 'success',
              message: 'Invoice cancelled!',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  addInternalNote = props => {
    let invoice = this.props.invoice;
    this.props.openModal({
      size: 'small',
      component: (
        <AddInternalNoteModal
          onSave={note => {
            return this._save({
              ...invoice,
              notes: {
                ...invoice.notes,
                ...note,
              },
            });
          }}
        />
      ),
    });
  };

  handleBackNavClick = () => {
    this.navigateToList();

    // TODO: should figure out why `anyTouched` & `dirty` flags are set to true
    // if (!(this.props.anyTouched && this.props.dirty)) {
    //   return this.navigateToList();
    // }

    // this.context
    //   .confirm({
    //     header: 'Unsaved changes',
    //     message: 'You have some unsaved changes. Do you want to leave this page ?',
    //     affirmativeLabel: 'Yes, leave',
    //     abortLabel: 'No, stay',
    //   })
    //   .then(() => {
    //     this.navigateToList();
    //   });
  };

  handleWindowClose() {
    // TODO: should figure out why `anyTouched` & `dirty` flags are set to true
    // window.onbeforeunload = function() {
    //   return this.props.anyTouched && this.props.dirty
    //     ? 'Unsaved changes will be deleted. Do you want to leave the page?'
    //     : null;
    // }.bind(this);
  }

  componentWillUnmount() {
    window.onbeforeunload = null;
  }

  render() {
    const { handleSubmit, customer, invoice } = this.props;

    let isTestMode = this.props.session.mode === 'test';
    let isNew = !invoice.id;
    let status = invoice.status;
    let isDraft = status === 'draft';
    let isIssued = status === 'issued';
    let isPaid = status === 'paid';
    let isCancelled = status === 'cancelled';
    let isExpired = status === 'expired';
    let locked = isPaid || isExpired || isCancelled;

    let invoiceTotal = this.calculateInvoiceTotal();

    return (
      <div class="react-root">
        {this.state.isLoading
          ? <div class="page-spinner-container">
              <Spinner />
            </div>
          : <div class="content-wrapper invoice-creation-container">
              <form onSubmit={handleSubmit(this.save)}>
                <div class="row">
                  <div class="col-md-8 col-sm-8">
                    <div class="invoice-container pull-right">
                      <InvoiceBreadcrumbNav
                        invoice={invoice}
                        onBackNavClick={this.handleBackNavClick}
                      />

                      <Alert
                        type={this.state.status.type}
                        message={this.state.status.message}
                      />

                      <div class="invoice">
                        {isTestMode &&
                          <div class="alert-sm alert-warning testmode-warning">
                            Invoice is created in <b>Test Mode</b>
                            . Only test payments can be made for this invoice
                          </div>}
                        <InvoiceLogo
                          logo={this.state.merchantLogoUrl}
                          name={this.state.merchantAltBillingLabel}
                        />

                        <div class="row">
                          <div class="col-md-6">
                            <div class="inv__titlesection">
                              <h3>Invoice</h3>
                              {locked && !invoice.receipt
                                ? <InlineField
                                    formName="newInvoice"
                                    name="id"
                                    component="input"
                                    class="form-control input-xs"
                                    disabled={true}
                                    size={30}
                                  />
                                : <InlineField
                                    formName="newInvoice"
                                    name="receipt"
                                    component="input"
                                    class="form-control input-xs"
                                    placeholder="Receipt number"
                                    disabled={locked}
                                  />}
                            </div>

                            <InlineField
                              formName="newInvoice"
                              name="description"
                              component={AutoResizeTextarea}
                              class="form-control input-xs"
                              placeholder="Summary or brief"
                              rows="1"
                              disabled={isIssued || locked}
                            />
                          </div>
                          <div class="col-md-6 text-right">
                            <label>AMOUNT DUE</label>
                            <h3 class="inv__amountdue">
                              {invoice.amount_due
                                ? <Amount
                                    value={invoice.amount_due}
                                    currency={invoice.currency}
                                  />
                                : <span>
                                    ₹ {invoiceTotal}
                                  </span>}
                            </h3>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-md-6">
                            <label>BILLING TO</label>
                            <InlineField
                              formName="newInvoice"
                              name="customer_id"
                              component={TypeAhead}
                              options={this.props.customers}
                              selected={this.props.customer_id}
                              optionLabelPath="displayName"
                              selectedOptionLabelPath="selectedDisplayName"
                              placeholder="Select a customer"
                              onQuickAdd={this.quickCreateCustomer}
                              disabled={isIssued || locked}
                              onOptionChange={selectedCustomer => {
                                this.props.change(
                                  'customer_id',
                                  selectedCustomer.id || ''
                                );
                              }}
                              normalizeValue={value => {
                                let selected = findBy(
                                  this.props.customers || [],
                                  'id',
                                  value
                                );
                                if (selected) {
                                  return selected.selectedDisplayName;
                                }
                                return value;
                              }}
                            />

                            {customer &&
                              <div class="inv__customerdetails">
                                {customer.name &&
                                  <div>
                                    {customer.contact}
                                  </div>}
                                {customer.name || customer.contact
                                  ? <div>
                                      {customer.email}
                                    </div>
                                  : ''}
                              </div>}
                          </div>
                          <div class="col-md-6 text-right">
                            <label>INVOICE DATE</label>
                            <InlineField
                              formName="newInvoice"
                              name="date"
                              component={DatePickerField}
                              class="form-control"
                              rightAlign={true}
                              isOutsideRange={day => {
                                let diff = moment().diff(day, 'hours') / 24;
                                return !(diff <= 60 && diff >= 0);
                              }}
                              normalizeValue={value => {
                                if (value) {
                                  return moment
                                    .unix(value)
                                    .format('DD MMM YYYY');
                                }
                                return value;
                              }}
                              disabled={isIssued || locked}
                            />
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-12">
                            <FieldArray
                              name="line_items"
                              component={LineItemTable}
                              items={this.props.items}
                              disabled={isIssued || locked}
                              invoice={invoice}
                              invoiceTotal={invoiceTotal}
                            />
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-md-12">
                            <ShowWhen featureEnabled="Invoice_Partial_Payments">
                              <div class="rzpCheckbox rzpCheckbox-sm">
                                <Field
                                  name="partial_payment"
                                  id="partial_payment"
                                  component="input"
                                  type="checkbox"
                                  disabled={locked}
                                />
                                <label for="partial_payment">
                                  Enable Partial Payments
                                </label>
                              </div>
                            </ShowWhen>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-md-12">
                            <InlineField
                              formName="newInvoice"
                              name="comment"
                              component={AutoResizeTextarea}
                              class="form-control input-xs"
                              rows={2}
                              placeholder="Customer Notes"
                              disabled={locked}
                            />
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-md-12">
                            <InlineField
                              formName="newInvoice"
                              name="terms"
                              component={AutoResizeTextarea}
                              class="form-control input-xs"
                              rows={3}
                              placeholder="Terms and Conditions"
                              disabled={locked}
                            />
                          </div>
                        </div>

                        <div class="inv__Footer">
                          <div class="inv__Footer__merchantName">
                            {this.state.merchantAltBillingLabel}
                          </div>
                          <div class="inv__Footer__merchantAddress">
                            {
                              this.props.session.user
                                .business_registered_address
                            }
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <ShowWhen notMyRole="support finance">
                    <div
                      class="col-md-4 col-sm-4"
                      style={{ marginTop: '48px' }}
                    >
                      {!locked &&
                        <div class="inv__cta">
                          <div class="btn-group-vertical">
                            {(isNew || isDraft) &&
                              <AsyncButton
                                type="button"
                                class="btn btn-primary btn-block btn-lg"
                                disabled={this.state.isSaving}
                                onClick={handleSubmit(props => {
                                  return this.saveAndIssue({
                                    ...props,
                                    ...{ draft: 0 },
                                  });
                                })}
                              >
                                <i class="icon icon-done" />
                                <span>Finalize and Issue</span>
                              </AsyncButton>}

                            {isIssued &&
                              <AsyncButton
                                type="button"
                                class="btn btn-primary btn-block btn-lg"
                                disabled={this.state.isSaving}
                                onClick={handleSubmit(this.resendInvoice)}
                              >
                                <i class="icon icon-send" />
                                <span>Resend Invoice</span>
                              </AsyncButton>}

                            {!locked &&
                              <AsyncButton
                                type="button"
                                class="btn btn-default btn-block btn-lg"
                                text="Save Invoice"
                                pendingText="Saving..."
                                disabled={this.state.isSaving}
                                onClick={handleSubmit(props => {
                                  return this.save({
                                    ...props,
                                    ...{ draft: isIssued ? 0 : 1 },
                                  });
                                })}
                              >
                                <i class="icon icon-save" />
                                <span>Save Invoice</span>
                              </AsyncButton>}
                            {(isNew || isDraft) &&
                              <button
                                type="button"
                                class="btn btn-default btn-block btn-lg"
                                onClick={this.deleteInvoice}
                                disabled={this.state.isSaving}
                              >
                                <i class="icon icon-close" />
                                <span>Delete Invoice</span>
                              </button>}
                            {isIssued &&
                              <button
                                type="button"
                                class="btn btn-default btn-block btn-lg"
                                onClick={this.cancelInvoice}
                                disabled={this.state.isSaving}
                              >
                                <i class="icon icon-close" />
                                <span>Cancel Invoice</span>
                              </button>}
                          </div>
                        </div>}

                      <InvoiceInfo invoice={invoice} />
                      <InvoiceNotes
                        invoice={invoice}
                        isSaving={this.state.isSaving}
                        onAddClick={this.addInternalNote}
                      />
                    </div>
                  </ShowWhen>
                </div>
              </form>
            </div>}
      </div>
    );
  }
}
