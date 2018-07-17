import { Fragment, Component } from 'react';
import PropTypes from 'prop-types';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import Amount from 'rzp/ui/Amount';
import Alert from 'rzp/ui/Forms/Alert';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import TypeAhead from 'rzp/ui/Select/TypeAhead';
import Spinner from 'rzp/ui/Spinner';
import InlineField from 'rzp/ui/Forms/InlineField';
import {
  findBy,
  getKeysSeparatedByPipe,
  getGSTSlabs,
  stringifyAddress,
  capitalize,
  isAddressValid,
  calculateTax,
} from 'rzp/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';

import LineItemTable from './LineItemTable';
import CustomerCreation from 'merchant/containers/Customers/New';
import IssueConfirmModal from './IssueConfirmModal';
import AddInternalNoteModal from './AddInternalNoteModal';
import InvoiceBreadcrumbNav from 'merchant/components/Invoices/InvoiceBreadcrumbNav';
import InvoiceInfo from 'merchant/components/Invoices/InvoiceInfo';
import InvoiceNotes from 'merchant/components/Invoices/InvoiceNotes';
import InvoiceLogo from 'merchant/components/Invoices/InvoiceLogo';
import {
  fetchCustomersForAutocomplete,
  fetchCustomerAddresses,
} from 'merchant/modules/customers';
import { fetchItemsForAutocomplete } from 'merchant/modules/items';
import { saveInvoice, deleteInvoice } from 'merchant/modules/invoices/list';
import { fetchStates } from 'merchant/modules/states';
import { fetchGSTTaxes } from 'merchant/modules/taxes';
import * as InvoiceActions from 'merchant/modules/invoices/details';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import { PowerSelect } from 'react-power-select';
import { SingleDatePicker } from 'react-dates';
import AddressSelectionModal from 'merchant/containers/Invoices/AddressSelectionModal/index';
import EditInvoiceLabelModal from 'merchant/containers/Invoices/Modals/Merchant/EditInvoiceLabel';
import AddressDisplay from 'merchant/components/AddressDisplay';
import * as constants from 'rzp/utils/constants';
import InvoicesOnboarding from 'merchant/containers/Invoices/Modals/Onboarding';
import { luminateRow } from 'merchant/modules/app';
import { track, trackLinkClick } from './ga';

function validate(values) {
  let errors = {
    line_items: [],
  };
  let lineItems = values.line_items.filter(
    item =>
      !!((item.item_id && item.item_id !== 'NULL') || item.id || item.name)
  );

  /**
   * Error text won't be shown because the custom components don't have support for error text.
   * However, currently, Finalize and Save Invoice buttons are being disabled if `validate` fails, so
   * flow doesn't come here.
   */
  if (!(values.customer && values.customer.id)) {
    if (!errors.customer) {
      errors.customer = {};
    }
    errors.customer.id = 'Please select a customer';
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
      customer: findBy(customers, 'id', selector(state, 'customer.id')),
      invoice: state.invoice.invoice,
      invoice_line_items: selector(state, 'line_items'),
      state_of_supply: selector(state, 'state_of_supply'),
      config: state.config.config,
      supply_state_code: selector(state, 'supply_state_code'),
    };
  },
  {
    luminateRow,
    fetchCustomersForAutocomplete,
    fetchItemsForAutocomplete,
    fetchCustomerAddresses,
    saveInvoice,
    deleteInvoice,
    fetchStates,
    fetchGSTTaxes,
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
    draft: '0',
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

  static defaultProps = {
    customer: {},
  };

  constructor() {
    super(...arguments);
    const issue_date = moment().startOf('day');
    this.state = {
      status: {},
      issue_date,
      today: issue_date,
      isFetchingAddresses: false,
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

  /**
   * Initializes the Invoice.
   * @param {Invoice} invoice
   */
  _initialize(invoice) {
    this.props.initialize(invoice);

    // Set issue date.
    if (invoice.date) {
      this.pickIssueDate(moment(invoice.date * 1000), false);
    }

    // Set expiry date.
    if (invoice.expire_by) {
      this.pickExpiryDate(moment(invoice.expire_by * 1000), false);
    }

    // Set Customer.
    let customerDetails = invoice.customer;

    if (customerDetails) {
      let customer =
        this.props.customers &&
        this.props.customers.find(c => c.id == customerDetails.id);

      if (customer) {
        let billingAddress, shippingAddress;

        billingAddress = customerDetails.billing_address_id;
        shippingAddress = customerDetails.shipping_address_id;

        this.onSelectCustomer(customer, billingAddress, shippingAddress, false);
      }
    }

    // Set State of Supply
    if (invoice.supply_state_code && this.state.states) {
      this.changeStateOfSupply({
        option: this.findStateByCode(
          invoice.supply_state_code,
          this.state.states
        ),
      });
    }

    // Refresh merchant info.
    setTimeout(() => this.getMerchantInfo());
  }

  componentWillMount() {
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
      this.props.fetchItemsForAutocomplete({
        type: 'invoice',
        'expand[]': 'tax',
      }),
      this.props.fetchStates(),
      this.props.fetchGSTTaxes(),
      ...promises,
    ];

    this.setState({
      isLoading: true,
    });

    Promise.all(promises)
      .then(([customers, items, states, gst, invoice]) => {
        if (invoice) {
          this._initialize(invoice);
        }

        let statesList = states && states.data && states.data.items;

        this.setState({
          isLoading: false,
          gst: gst && gst.data,
          states: statesList,
        });

        // Set state of supply.
        if (
          statesList &&
          statesList.length > 0 &&
          this.props.supply_state_code
        ) {
          this.changeStateOfSupply({
            option: this.findStateByCode(
              this.props.supply_state_code,
              statesList
            ),
          });
        }
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
    let user = this.props.session.user;
    let merchant = user.merchants[user.current];
    let logoUrl = this.props.config.logo_url;
    let gstin = user.gstin || user.p_gstin;
    let cin = user.company_cin;

    let { config: { invoice_label_field } } = this.props;

    let merchantAltBillingLabel = user.business_name || user.business_dba;
    if (invoice_label_field && user[invoice_label_field]) {
      merchantAltBillingLabel = user[invoice_label_field];
    }

    // Set label and GSTIN from the invoice.
    if (
      this.props.invoice &&
      this.props.invoice.status &&
      this.props.invoice.status !== 'draft'
    ) {
      const { merchant_gstin, merchant_label } = this.props.invoice;

      if (typeof merchant_gstin !== 'undefined') {
        gstin = merchant_gstin;
      }

      // Not using the undefined check here because GSTIN was not in Invoice v1.
      if (merchant_label) {
        merchantAltBillingLabel = merchant_label;
      }
    }

    this.setState({
      merchant,
      merchantLogoUrl: logoUrl,
      merchantAltBillingLabel,
      merchantGSTIN: gstin,
      merchantCIN: cin,
    });
  }

  calculateItemsSubTotal() {
    let lineItems = this.props.invoice_line_items || [];

    // Apply taxes only if the merchant has a GSTIN and state of supply is selected;
    let user = this.props.session.user;
    let merchantGstin = user.gstin || user.p_gstin;

    const applyTaxes =
      Boolean(merchantGstin) && Boolean(this.props.state_of_supply);

    // Get the cost of items without considering the tax on tax_exclusive items.
    let subtotal = lineItems
      .reduce((total, line_item) => {
        let totalAmt =
          Number(line_item.quantity) * Number(line_item.amountInINR);

        return total + totalAmt;
      }, 0)
      .toFixed(2);

    // Get the total tax applicable on all items.
    let totalTax = 0.0;
    if (applyTaxes) {
      totalTax = lineItems
        .reduce((total, line_item) => {
          let totalAmt =
            Number(line_item.quantity) * Number(line_item.amountInINR);

          // Add taxes
          let tax = 0;
          let cess = 0;

          if (line_item.tax_rate) {
            tax = calculateTax(
              totalAmt,
              line_item.tax_rate / 100,
              line_item.tax_inclusive
            );
          }

          if (applyTaxes && line_item.cess) {
            cess = calculateTax(
              totalAmt,
              line_item.cess / 100,
              line_item.tax_inclusive
            );
          }

          return total + cess + tax;
        }, 0)
        .toFixed(2);
    }

    let total = lineItems
      .reduce((_total, line_item) => {
        let totalAmt =
          Number(line_item.quantity) * Number(line_item.amountInINR);

        let tax = 0;
        let cess = 0;

        // Add taxes
        if (applyTaxes && !line_item.tax_inclusive) {
          if (line_item.tax_rate) {
            tax = calculateTax(totalAmt, line_item.tax_rate / 100);
          }

          if (applyTaxes && line_item.cess) {
            cess = calculateTax(totalAmt, line_item.cess / 100);
          }
        }

        return _total + totalAmt + cess + tax;
      }, 0)
      .toFixed(2);

    return {
      total,
      tax: totalTax,
      subtotal,
    };
  }

  calculateInvoiceTotal() {
    return this.calculateItemsSubTotal();
  }

  /**
   * Sets the customer in props.
   * @param {Customer} customer
   */
  setCustomerInProps = customer => {
    this.props.change('customer.id', customer.id);
    this.props.change('customer.name', customer.name);
    this.props.change('customer.contact', customer.contact);
    this.props.change('customer.email', customer.email);
    this.props.change('customer.gstin', customer.gstin);
  };

  /**
   * Returns a clousure method to select customers.
   * @param {Boolean} updateAddress Whether or not addresses should be updated (an API call should be made to fetch addresses)
   * @param {Boolean} selectShippingAddress Whether or not to select shipping address.
   *
   * @return {Function}
   *    @param {Customer} customer Selected/Created customer.
   *    @param {Boolean} shippingSameAsBilling whether or not shipping address to be used is the same as billing address. To be used for new customers.
   */
  selectCustomerAndCloseModal = (
    updateAddress = false,
    selectShippingAddress = false
  ) => (customer, shippingSameAsBilling = false) => {
    this.setCustomerInProps(customer);
    this.props.closeModal();

    if (updateAddress) {
      this.fetchCustomerAddresses(
        customer.id,
        undefined,
        undefined,
        selectShippingAddress,
        shippingSameAsBilling
      );
    }

    track({
      eventAction: `Close Form - ${updateAddress ? 'Add' : 'Edit'} Customer`,
    });
  };

  /**
   * Fetches customer's addresses and selects one billing and shipping address each.
   * If the IDs are passed, it will try to select those addresses instead of automatically
   * picking the primary address.
   *
   * billingAddressID === null signifies we don't have to autoselect a billing address.
   * shippingAddressID === null signifies we don't have to autoselect a shipping address.
   *
   * @param {String} customerID Customer ID.
   * @param {String} billingAddressID Billing address ID to select.
   * @param {String} shippingAddressID Shipping address ID to select.
   * @param {Boolean} selectShippingAddress Whether or not to select shipping address.
   * @param {Boolean} shippingSameAsBilling Is shipping address same as billing address.
   * @param {Boolean} autoselectPlaceOfSupply Whether or not to autoselect place of supply.
   */
  fetchCustomerAddresses = (
    customerID,
    billingAddressID,
    shippingAddressID,
    selectShippingAddress = false,
    shippingSameAsBilling = false,
    autoselectPlaceOfSupply = true
  ) => {
    // Set loading state.
    this.setState({
      isFetchingAddresses: true,
    });

    return this.props
      .fetchCustomerAddresses({
        id: customerID,
      })
      .then(response => {
        if (!response.success) return;

        // Set shipping and billing addresses based on their types.
        let addresses = response.data.items;
        let selected_billing;
        let selected_shipping;

        // If a billing address ID is given, try to set it as selected_billing.
        if (billingAddressID) {
          selected_billing = addresses.find(
            address => address.id == billingAddressID
          );
        }

        // If a shipping address ID is given, try to set it as selected_shipping.
        if (shippingAddressID) {
          selected_shipping = addresses.find(
            address => address.id == shippingAddressID
          );
        }

        if (billingAddressID !== null) {
          // Select the addresses which have primary=true
          if (!selected_billing) {
            selected_billing = addresses.find(
              address => address.type === 'billing_address'
            );
          }
        }

        if (shippingAddressID !== null) {
          if (!selected_shipping && shippingSameAsBilling) {
            selected_shipping = selected_billing;
          }

          if (!selected_shipping && selectShippingAddress) {
            selected_shipping = addresses.find(
              address => address.type === 'shipping_address'
            );
          }
        }

        if (billingAddressID !== null) {
          // If primary=false on all addresses, set the first address as selected.
          if (!selected_billing && addresses.length >= 1) {
            selected_billing = addresses[0];
          }
        }

        // Set addresses in state
        this.setState({
          addresses,
          isFetchingAddresses: false,
        });

        // Select addresses
        this.selectBillingAddress(selected_billing, autoselectPlaceOfSupply);
        this.selectShippingAddress(selected_shipping);
      })
      .catch(error => {
        this.props.showNotification({
          type: 'error',
          message: error.errors,
        });
        throw error;
      });
  };

  quickCreateCustomer = ({ searchTerm = '' }) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Create Customer"
          onSave={this.selectCustomerAndCloseModal(true, true)}
          customer={{
            name: searchTerm,
          }}
        />
      ),
    });
  };

  /**
   * Shows the Edit Customer modal.
   * @param {DOMEvent} e
   */
  quickEditCustomer = e => {
    e && e.preventDefault();

    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Update Customer"
          onSave={this.selectCustomerAndCloseModal()}
          customer={this.props.customer}
        />
      ),
    });
  };

  /**
   * Shows the onboarding modal.
   */
  showOnboardingModal = () => {
    const onStart = () => {
      track({
        eventAction: 'Click - Start Creating Invoices',
      });
      this.props.closeModal();
      this.getMerchantInfo();
    };

    const onCloseClick = () => {
      this.props.closeModal();
      this.navigateToList();
    };

    this.props.openModal({
      size: 'large',
      component: (
        <InvoicesOnboarding
          merchant={this.props.session.user}
          onStart={onStart}
          onCloseClick={onCloseClick}
        />
      ),
    });
  };

  /**
   * Method to set the selected billing address.
   * @param {Object} address
   * @param {Boolean [Optional]} autoselectPlaceOfSupply Whether or not to autoselect place of supply.
   */
  selectBillingAddress = (address, autoselectPlaceOfSupply = true) => {
    this.setState(
      {
        selectedBillingAddress: address,
      },
      () => {
        if (autoselectPlaceOfSupply) {
          // Update State of Supply using the billing address state.
          if (address && address.state) {
            this.changeStateOfSupply({
              option: this.findStateByName(address.state, this.state.states),
            });
          }
        }
      }
    );

    // Update billing address ID.
    this.props.change(
      'customer.billing_address_id',
      address ? address.id : null
    );
  };

  /**
   * Method to set the selected shipping address.
   * @param {Object} address
   */
  selectShippingAddress = address => {
    this.setState({
      selectedShippingAddress: address,
    });

    // Update shipping address ID.
    this.props.change(
      'customer.shipping_address_id',
      address ? address.id : null
    );
  };

  /**
   * Shows the Edit Invoice Label modal.
   * @param {DOMEvent} e
   */
  showEditInvoiceLabelModal = e => {
    e.preventDefault();

    track({
      eventAction: 'Change - Invoice Label',
    });

    let { session: { user }, config: { invoice_label_field } } = this.props;

    const onSave = () => {
      this.props.closeModal();
      this.getMerchantInfo();
    };

    this.props.openModal({
      size: 'small',
      component: (
        <EditInvoiceLabelModal
          merchant={user}
          current={invoice_label_field}
          onSave={onSave}
        />
      ),
    });
  };

  /**
   * Returns a handler to show Address Selection Modal.
   * @param {String} type One of "billing" and "shipping".
   */
  showSelectAddressModal = (type = 'billing') => e => {
    e.preventDefault();

    track({
      eventAction: 'Change - Address',
      eventLabel: capitalize(type),
    });

    type = type.toLowerCase();

    let {
      selectedBillingAddress,
      selectedShippingAddress,
      addresses = [],
    } = this.state;

    let { customer } = this.props;

    // Return if the customer is not yet selected.
    if (!customer) return;

    /**
     * Handler for when an address is created.
     */
    const onSave = address => {
      // Select address.
      if (type === 'billing') {
        this.selectBillingAddress(address);
      } else {
        this.selectShippingAddress(address);
      }

      // Add address to master list.
      addresses.push(address);
      this.setState({
        addresses,
      });

      // Close Modal
      this.props.closeModal();
    };

    let actionText = 'Add';
    if (
      (type === 'billing' && selectedBillingAddress) ||
      (type === 'shipping' && selectedShippingAddress)
    ) {
      actionText = 'Change';
    }

    this.props.openModal({
      size: 'small',
      component: (
        <AddressSelectionModal
          header={`${actionText} ${capitalize(type)} Address`}
          customer={customer}
          addresses={addresses}
          selected={
            type === 'billing'
              ? selectedBillingAddress
              : selectedShippingAddress
          }
          onSelect={
            type === 'billing'
              ? this.selectBillingAddress
              : this.selectShippingAddress
          }
          onSave={onSave}
          addressType={type}
        />
      ),
    });
  };

  /**
   * Callback for when an address is selected as the Default Address
   * @param {Object} address
   */
  onSetDefaultAddress = address => {
    // Do something with the address, make a n/w request or something.
  };

  /**
   * Prepares props to be saved.
   * Updates props in place and also returns them.
   * @param {Object} props
   * @return {Object}
   */
  prepareForSave = props => {
    const isExistingInvoice = props.id;

    /**
     * Get code if state of supply is present.
     * Set to null if the state of supply is null and this is an old invoice.
     * Delete if state of supply is null and this is a new invoice.
     */
    if (props.state_of_supply) {
      if (props.state_of_supply && typeof props.state_of_supply === 'object') {
        props.supply_state_code = props.state_of_supply.code;
      }
    } else if (isExistingInvoice && props.state_of_supply === null) {
      props.supply_state_code = null;
    } else {
      delete props.supply_state_code;
    }

    // Set the expiry time to be at 23:59:59 on the day selected.
    if (props.expire_by) {
      props.expire_by = moment
        .unix(props.expire_by)
        .endOf('day')
        .unix();
    }

    return props;
  };

  _save(props) {
    props = this.prepareForSave(props);

    this.setState({
      isSaving: true,
    });
    return this.props
      .saveInvoice(props, {
        'Content-Type': 'application/json',
      })
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
      track({
        eventAction: 'Save - Invoice',
        eventLabel: getKeysSeparatedByPipe(props),
      });
      this.props.showNotification({
        type: 'success',
        message: 'Invoice Saved',
      });
      this.props.history.push(`/invoices/${invoice.id}`);
      this._initialize(invoice);
      return invoice;
    });
  };

  saveAndIssue(props) {
    // Tag Hotjar if the invoice is issued..
    if (!(this.props.invoice && this.props.invoice.id)) {
      if (typeof window.hj === 'function') {
        window.hj('trigger', 'invoice_issue');
        window.hj('tagRecording', ['invoice_issue']);
      }
    }

    return this.showIssueConfirmModal(notifyProps => {
      return this._save({
        ...props,
        ...notifyProps,
      }).then(invoice => {
        track({
          eventAction: 'Issue - Invoice',
          eventLabel: getKeysSeparatedByPipe(props),
        });
        this.props.showNotification({
          type: 'success',
          message: `Invoice ${invoice.id} created successfully`,
        });
        this.navigateToList();
        this.props.luminateRow(invoice.id);
        return invoice;
      });
    });
  }

  resendInvoice = props => {
    this.showIssueConfirmModal(notifyProps => {
      // Update invoice and then resend.

      return this._save(props)
        .then(invoice => {
          let promises = [];

          if (notifyProps.email_notify) {
            promises.push(this.props.notifyCustomer(props, 'email'));
          }
          if (notifyProps.sms_notify) {
            promises.push(this.props.notifyCustomer(props, 'sms'));
          }

          return Promise.all(promises)
            .then(([emailStatus, smsStatus]) => {
              track({
                eventAction: 'Resend - Invoice',
                eventLabel: getKeysSeparatedByPipe(props),
              });
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
      message: () => (
        <div class="text-semi-muted">
          <p>
            The Invoice will be deleted. There is no coming back!. Are you sure?
          </p>
          <div>
            If you have added any item or customer, you can still use them in
            other invoices.
          </div>
        </div>
      ),
      affirmativeLabel: 'Yes, Delete',
      affirmativePendingLabel: 'Deleting...',
      abortLabel: "No, don't!",
      action: () => {
        return invoice.id
          ? this.props
              .deleteInvoice(invoice)
              .then(() => {
                track({
                  eventAction: 'Delete - Invoice',
                  eventLabel: `invoice_id=${invoice.id}`,
                });
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
      message: () => (
        <div class="text-semi-muted">
          <p>
            The Invoice will be cancelled and the customer will not be able to
            pay for it.
          </p>
        </div>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .cancelInvoice(invoice)
          .then(invoice => {
            track({
              eventAction: 'Cancel - Invoice',
              eventLabel: `invoice_id=${invoice.id}`,
            });
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

  /**
   * Finds a state by it's code.
   * @param {Number} code State code
   * @param {Array} states Array of state objects (this.state.states)
   * @return {Object}
   */
  findStateByCode = (code, states) => states.find(o => o.code === code);

  /**
   * Finds a state by it's name.
   * @param {String} name State name
   * @param {Array} state Array of state objects (this.state.states)
   * @return {Object}
   */
  findStateByName = (name, states) => states.find(o => o.name === name);

  /**
   * Sets Issue Date.
   * @param {MomentObj} date
   * @param {Boolean} reset Whether or not to reset the time to 0000 hours.
   */
  pickIssueDate = (date, reset = true) => {
    if (!date) return;

    // Set issue date.
    if (reset) {
      date = date.startOf('day');
    }

    let { expiry_date } = this.state;

    /**
     * If expiry date is before the selected date
     * unset expiry date.
     */
    if (expiry_date) {
      let diff = date.diff(expiry_date, 'd', true);
      if (diff > 0) {
        expiry_date = null;
        this.props.change('expire_by', expiry_date);
      }
    }

    this.props.change('date', date.unix());

    this.setState({
      issue_date: date,
      expiry_date,
    });
  };

  /**
   * Sets Expiry Date
   * @param {MomentObj} date
   * @param {Boolean} reset Whether or not to reset the time to 0000 hours.
   */
  pickExpiryDate = (date, reset = true) => {
    if (date) {
      if (reset) {
        date = date.startOf('day');
      }
      this.props.change('expire_by', date.unix());
    } else {
      this.props.change('expire_by', null);
    }

    this.setState({
      expiry_date: date,
    });
  };

  /**
   * Returns false for all the dates after today.
   * @param {MomentObj} date
   * @return {Boolean}
   */
  issueDateRange = date => {
    date = date.startOf('day');

    return this.isDateAfter(date, this.state.today);
  };

  /**
   * Returns false for all the dates before today.
   * @param {MomentObj} date
   * @return {Boolean}
   */
  expiryDateRange = date => {
    date = date.startOf('day');

    return this.isDateBefore(date, this.state.today);
  };

  componentWillUnmount() {
    let action;
    if (!this.props.match.params.id) {
      action = 'Close Form - New Invoice';
    } else {
      action = 'Close Details - Invoice';
    }
    track({
      eventAction: action,
    });
    window.onbeforeunload = null;

    // Tag Hotjar if there's a draft.
    if (!(this.props.invoice && this.props.invoice.id)) {
      if (typeof window.hj === 'function') {
        window.hj('trigger', 'invoice_unsaved');
        window.hj('tagRecording', ['invoice_unsaved']);
      }
    }
  }

  componentDidMount() {
    this.getMerchantInfo();

    let action;
    if (!this.props.match.params.id) {
      action = 'Open Form - New Invoice';
    } else {
      action = 'Open Details - Invoice';
    }
    track({
      eventAction: action,
    });

    /**
     * Show onboarding modal if invoice_label_field is null.
     */
    let { invoice_label_field } = this.props.config;

    if (invoice_label_field === null) {
      this.showOnboardingModal();
    }
  }

  /**
   * Methods to compare two dates.
   * @param {Moment} base Base date
   * @param {Moment} d Date to compare
   * @return {Boolean}
   */
  isDateAfter = (base, d) => base && d && base.diff(d, 'days', true) > 0.0;
  isDateBefore = (base, d) => base && d && base.diff(d, 'days', true) < 0.0;
  isDateSame = (base, d) => base && d && base.diff(d, 'days', true) == 0.0;

  /**
   * Update state of supply and then update the applicable GST slabs.
   */
  changeStateOfSupply = ({ option = null }) => {
    this.props.change('state_of_supply', option);

    this.updateGSTSlabs(option);
  };

  /**
   * Updates the GST slabs based on the state of supply.
   * @prop {State} stateOfSupply State of Supply
   */
  updateGSTSlabs = stateOfSupply => {
    let { merchantGSTIN, gst } = this.state;

    // If the merchant doesn't have a GSTIN, stop.
    if (!merchantGSTIN) {
      return;
    }

    // If the state of supply is empty, clear slabs.
    if (!stateOfSupply) {
      return this.setState({
        gstSlabs: null,
      });
    }

    // Get the merchant's state.
    let merchantState = this.findStateByCode(
      merchantGSTIN.slice(0, 2),
      this.state.states
    );

    // Get the applicable groups and slabs and set them in state.
    const gstSlabs = getGSTSlabs(
      gst.gst_tax_slabs, // [0, 500, 1200, ...]
      merchantState.code, // "29"
      stateOfSupply.code, // "29"
      gst.gst_tax_id_map, // {CGST_0: "tax_1234", CGST_250: "tax_3456", ...}
      stateOfSupply.is_ut || merchantState.is_ut // Whether or not any of the states is a Union Territory
    );
    this.setState({
      gstSlabs,
    });
  };

  /**
   * When customer is selected.
   * @param {Object} selectedCustomer customer
   * @param {String} billingAddressID Billing address ID to select.
   * @param {String} shippingAddressID Shipping address ID to select.
   * @param {Boolean} autoselectPlaceOfSupply Whether or not to autoselect place of supply.
   */
  onSelectCustomer = (
    selectedCustomer,
    billingAddressID,
    shippingAddressID,
    autoselectPlaceOfSupply
  ) => {
    if (!selectedCustomer) return;

    this.setCustomerInProps(selectedCustomer);

    // Unset State of Supply
    this.changeStateOfSupply({
      option: null,
    });

    // Unset addresses.
    this.selectBillingAddress(null);
    this.selectShippingAddress(null);

    // Fetch and reset addresses.
    this.fetchCustomerAddresses(
      selectedCustomer.id,
      billingAddressID,
      shippingAddressID,
      false,
      false,
      autoselectPlaceOfSupply
    );
  };

  render() {
    const { handleSubmit, customer, invoice, session: { user } } = this.props;

    let isTestMode = this.props.session.mode === 'test';
    let isNew = !invoice.id;
    let status = invoice.status;
    let isDraft = status === 'draft';
    let isIssued = status === 'issued';
    let isPaid = status === 'paid';
    let isPartiallyPaid = status === 'partially_paid';
    let isCancelled = status === 'cancelled';
    let isExpired = status === 'expired';
    let locked = isPartiallyPaid || isPaid || isExpired || isCancelled;

    let invoiceTotal = this.calculateInvoiceTotal();

    // Merchant Address object used to show the address in footer.
    const merchantAddress = {
      line1: this.props.session.user.business_registered_address,
      city: this.props.session.user.business_registered_city,
      state:
        constants.states[this.props.session.user.business_registered_state],
      country: 'India',
      zipcode: this.props.session.user.business_registered_pin,
    };

    const {
      gstSlabs,
      issue_date,
      expiry_date,
      paymentDueDaysPending = 0,
      merchantGSTIN,
      merchantCIN,
      selectedBillingAddress,
      selectedShippingAddress,
      isFetchingAddresses,
    } = this.state;

    /**
     * Flag to toggle Update Invoice Label button.
     * Button should only be shown when the business_name and business_dba are different,
     * and the invoice has not yet been issued.
     */
    const showEditInvoiceLabelOption =
      user &&
      user.business_name &&
      user.business_dba &&
      user.business_name !== user.business_dba &&
      !(locked || isIssued);

    const isDisabled = isIssued || locked;

    const areBillingAddressActionsVisible =
      selectedBillingAddress &&
      customer &&
      customer.id &&
      !isFetchingAddresses &&
      !isDisabled;
    const areShippingAddressActionsVisible =
      selectedShippingAddress &&
      customer &&
      customer.id &&
      !isFetchingAddresses &&
      !isDisabled;

    return (
      <div class="react-root">
        {this.state.isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="content-wrapper invoice-creation-container">
            <form onSubmit={handleSubmit(this.save)}>
              <div class="row">
                <div class="col-md-8 col-sm-8">
                  <div class="invoice-container">
                    <InvoiceBreadcrumbNav
                      invoice={invoice}
                      onBackNavClick={this.handleBackNavClick}
                    />

                    <Alert
                      type={this.state.status.type}
                      message={this.state.status.message}
                    />

                    <div class="invoice">
                      {isTestMode && (
                        <div class="alert-sm alert-warning testmode-warning">
                          Invoice is created in <b>Test Mode</b>
                          . Only test payments can be made for this invoice
                        </div>
                      )}
                      <InvoiceLogo
                        logo={this.state.merchantLogoUrl}
                        name={this.state.merchantAltBillingLabel}
                        gstin={merchantGSTIN}
                        cin={merchantCIN}
                      />

                      <div class="row">
                        <div class="col-md-12">
                          <div class="inv__titlesection">
                            <h3>Invoice</h3>
                            {locked && !invoice.receipt ? (
                              <InlineField
                                formName="newInvoice"
                                name="id"
                                component="input"
                                class="form-control input-xs"
                                disabled={true}
                                size={30}
                              />
                            ) : (
                              <InlineField
                                formName="newInvoice"
                                name="receipt"
                                component="input"
                                class="material-input"
                                placeholder={`${
                                  !locked ? 'Enter ' : ''
                                }Invoice Number`}
                                disabled={locked}
                                keepValueInBG={false}
                              />
                            )}
                          </div>
                        </div>
                        <div class="col-md-12">
                          <div class="inv__description">
                            <InlineField
                              formName="newInvoice"
                              name="description"
                              component={AutoResizeTextarea}
                              class="material-input"
                              placeholder={`${
                                !isDisabled ? 'Enter a ' : ''
                              }Brief Description or Summary`}
                              rows="1"
                              keepValueInBG={false}
                              disabled={isDisabled}
                            />
                          </div>
                        </div>
                      </div>
                      {(invoice.amount_due && invoice.amount_due > 0.0) ||
                      (invoiceTotal &&
                        invoiceTotal.total &&
                        invoiceTotal.total > 0.0) ? (
                        <div class="row">
                          <div class="col-md-12">
                            <div>
                              <label class="inv__amountduetitle">
                                AMOUNT DUE
                              </label>
                              <h3 class="inv__amountdue">
                                {invoice.amount_due ? (
                                  <Amount
                                    value={invoice.amount_due}
                                    currency={invoice.currency}
                                  />
                                ) : (
                                  <span>₹ {invoiceTotal.total}</span>
                                )}
                              </h3>
                            </div>
                          </div>
                        </div>
                      ) : null}
                      <div class="row inv__customersection">
                        <div class="col-md-6 inv__customerselectionsection">
                          <div>
                            <label>BILLING TO</label>
                            <div>
                              {customer &&
                                customer.id &&
                                !isDisabled && (
                                  <button
                                    className="btn btn-sm btn-link edit-in-input"
                                    onClick={this.quickEditCustomer}
                                    type="button"
                                  >
                                    Edit
                                  </button>
                                )}
                              <InlineField
                                formName="newInvoice"
                                name="customer.id"
                                class="material-input"
                                component={TypeAhead}
                                options={this.props.customers}
                                selected={this.props.customer.id}
                                optionLabelPath="displayName"
                                selectedOptionLabelPath="selectedDisplayName"
                                placeholder="Select a customer"
                                onQuickAdd={this.quickCreateCustomer}
                                disabled={isDisabled}
                                labelWhenSearchTermBlank="Create new Customer"
                                labelWhenSearchTermValid="Add ':_searchTerm_:' as a Customer"
                                maxSearchTermLength="12"
                                keepValueInBG={false}
                                onOptionChange={this.onSelectCustomer}
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
                            </div>
                            {customer &&
                              customer.id && (
                                <div class="inv__customerdetails">
                                  {customer.name && (
                                    <div>{customer.contact}</div>
                                  )}
                                  {customer.name || customer.contact ? (
                                    <div>{customer.email}</div>
                                  ) : (
                                    ''
                                  )}
                                  {customer.gstin && (
                                    <div>
                                      <span class="tax-heading">GSTIN - </span>
                                      {customer.gstin}
                                    </div>
                                  )}
                                </div>
                              )}
                          </div>

                          <div class="inv__dates-container hidden-sm">
                            <div class="row">
                              <div class="col-md-4">
                                <label class="text-uppercase">Issue Date</label>
                              </div>
                              <div class="col-md-8">
                                <div
                                  class={`material-input__datepicker ${
                                    this.state.issue_date_focused
                                      ? 'material-input__datepicker-focused'
                                      : ''
                                  }`}
                                >
                                  <SingleDatePicker
                                    customInputIcon={
                                      <i class="i i-date-range" />
                                    }
                                    noBorder={true}
                                    disabled={isDisabled}
                                    placeholder="Issue Date"
                                    showClearDate={false}
                                    numberOfMonths={1}
                                    hideKeyboardShortcutsPanel={true}
                                    date={this.state.issue_date}
                                    focused={this.state.issue_date_focused}
                                    onFocusChange={({ focused }) =>
                                      this.setState({
                                        issue_date_focused: focused,
                                      })
                                    }
                                    onDateChange={this.pickIssueDate}
                                    displayFormat="MMM DD, YYYY"
                                    isOutsideRange={this.issueDateRange}
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="row">
                              <div>
                                <div class="col-md-4">
                                  <label class="text-uppercase">
                                    Expiry Date
                                  </label>
                                </div>
                                <div class="col-md-8">
                                  <div
                                    class={`material-input__datepicker ${
                                      this.state.expiry_date_focused
                                        ? 'material-input__datepicker-focused'
                                        : ''
                                    }`}
                                  >
                                    <SingleDatePicker
                                      customInputIcon={
                                        <i class="i i-date-range" />
                                      }
                                      noBorder={true}
                                      disabled={locked}
                                      placeholder="Expiry Date"
                                      showClearDate={true}
                                      numberOfMonths={1}
                                      hideKeyboardShortcutsPanel={true}
                                      date={this.state.expiry_date}
                                      focused={this.state.expiry_date_focused}
                                      onFocusChange={({ focused }) =>
                                        this.setState({
                                          expiry_date_focused: focused,
                                        })
                                      }
                                      onDateChange={this.pickExpiryDate}
                                      displayFormat="MMM DD, YYYY"
                                      isOutsideRange={this.expiryDateRange}
                                    />
                                    <div
                                      class={`Input-info ${
                                        this.state.expiry_date_focused
                                          ? 'show-info'
                                          : ''
                                      }`}
                                    >
                                      Expiry Date is the date after which the
                                      customer will be unable to pay for this
                                      Invoice.
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="row">
                            <div class="col-md-12">
                              <div class="inv__address-container">
                                <label class="text-uppercase">
                                  Billing Address
                                </label>
                                <span
                                  class={`two-btn-group ${
                                    areBillingAddressActionsVisible
                                      ? ''
                                      : 'invisible'
                                  }`}
                                >
                                  <button
                                    class="btn btn-sm btn-link"
                                    onClick={this.showSelectAddressModal(
                                      'billing'
                                    )}
                                    type="button"
                                  >
                                    Change
                                  </button>
                                  <button
                                    class="btn btn-sm btn-link"
                                    onClick={() => {
                                      this.selectBillingAddress(null);
                                      track({
                                        eventAction: 'Remove Address',
                                        eventLabel: 'Billing',
                                      });
                                    }}
                                    type="button"
                                  >
                                    Remove
                                  </button>
                                </span>
                                <div class="inv__address-container">
                                  {selectedBillingAddress ? (
                                    stringifyAddress(selectedBillingAddress)
                                  ) : (
                                    <div class="light-placeholder">
                                      {isFetchingAddresses ? (
                                        <Fragment>Loading...</Fragment>
                                      ) : !isDisabled ? (
                                        customer && customer.id ? (
                                          <button
                                            class="btn btn-link"
                                            onClick={this.showSelectAddressModal(
                                              'billing'
                                            )}
                                            type="button"
                                          >
                                            + Add Billing Address
                                          </button>
                                        ) : (
                                          <Fragment>
                                            Select customer to add Billing
                                            Address
                                          </Fragment>
                                        )
                                      ) : (
                                        <Fragment>
                                          Billing Address not applicable.
                                        </Fragment>
                                      )}
                                    </div>
                                  )}
                                </div>
                              </div>
                              <div class="inv__address-container inv__address-container-shipping">
                                <label class="text-uppercase">
                                  Shipping Address
                                </label>
                                <span
                                  class={`two-btn-group ${
                                    areShippingAddressActionsVisible
                                      ? ''
                                      : 'invisible'
                                  }`}
                                >
                                  <button
                                    class="btn btn-sm btn-link"
                                    onClick={this.showSelectAddressModal(
                                      'shipping'
                                    )}
                                    type="button"
                                  >
                                    Change
                                  </button>
                                  <button
                                    class="btn btn-sm btn-link"
                                    onClick={() => {
                                      this.selectShippingAddress(null);
                                      track({
                                        eventAction: 'Remove Address',
                                        eventLabel: 'Shipping',
                                      });
                                    }}
                                    type="button"
                                  >
                                    Remove
                                  </button>
                                </span>
                                <div class="inv__address-container">
                                  {selectedShippingAddress ? (
                                    stringifyAddress(selectedShippingAddress)
                                  ) : (
                                    <div class="light-placeholder">
                                      {isFetchingAddresses ? (
                                        <Fragment>Loading...</Fragment>
                                      ) : !isDisabled ? (
                                        customer && customer.id ? (
                                          <button
                                            class="btn btn-link"
                                            onClick={this.showSelectAddressModal(
                                              'shipping'
                                            )}
                                            type="button"
                                          >
                                            + Add Shipping Address
                                          </button>
                                        ) : (
                                          <Fragment>
                                            Select customer to add Shipping
                                            Address
                                          </Fragment>
                                        )
                                      ) : (
                                        <Fragment>
                                          Shipping Address not applicable.
                                        </Fragment>
                                      )}
                                    </div>
                                  )}
                                </div>
                              </div>
                              {merchantGSTIN && (
                                <div class="inv__place-of-supply-container">
                                  <label class="text-uppercase">
                                    Place of Supply
                                  </label>
                                  {!isDisabled ? (
                                    <Fragment>
                                      <div>
                                        <PowerSelect
                                          class="inv__state-of-delivery-list material-input"
                                          placeholder="Select from Dropdown"
                                          options={this.state.states || []}
                                          selected={this.props.state_of_supply}
                                          optionLabelPath="name"
                                          onChange={this.changeStateOfSupply}
                                          disabled={isDisabled}
                                        />
                                      </div>
                                      {!this.props.state_of_supply && (
                                        <div class="alert-sm alert-warning">
                                          <i class="i i-info-circle" />
                                          Add a Place of Supply to apply taxes
                                        </div>
                                      )}
                                    </Fragment>
                                  ) : this.props.state_of_supply ? (
                                    <div>{this.props.state_of_supply.name}</div>
                                  ) : (
                                    <div class="light-placeholder">
                                      Place of Supply not applicable.
                                    </div>
                                  )}
                                </div>
                              )}
                            </div>
                            <div class="col-md-12 hidden-md visible-sm-block">
                              <div class="inv__dates-container">
                                <div class="row">
                                  <div class="col-md-4">
                                    <label class="text-uppercase">
                                      Issue Date
                                    </label>
                                  </div>
                                  <div class="col-md-8">
                                    <div
                                      class={`material-input__datepicker ${
                                        this.state.issue_date_mobile_focused
                                          ? 'material-input__datepicker-focused'
                                          : ''
                                      }`}
                                    >
                                      <SingleDatePicker
                                        customInputIcon={
                                          <i class="i i-date-range" />
                                        }
                                        noBorder={true}
                                        disabled={isDisabled}
                                        placeholder="Issue Date"
                                        showClearDate={false}
                                        numberOfMonths={1}
                                        hideKeyboardShortcutsPanel={true}
                                        date={this.state.issue_date}
                                        focused={
                                          this.state.issue_date_mobile_focused
                                        }
                                        onFocusChange={({ focused }) =>
                                          this.setState({
                                            issue_date_mobile_focused: focused,
                                          })
                                        }
                                        onDateChange={this.pickIssueDate}
                                        displayFormat="MMM DD, YYYY"
                                        isOutsideRange={this.issueDateRange}
                                      />
                                    </div>
                                  </div>
                                </div>
                                <div class="row">
                                  <div>
                                    <div class="col-md-4">
                                      <label class="text-uppercase">
                                        Expiry Date
                                      </label>
                                    </div>
                                    <div class="col-md-8">
                                      <div
                                        class={`material-input__datepicker ${
                                          this.state.expiry_date_mobile_focused
                                            ? 'material-input__datepicker-focused'
                                            : ''
                                        }`}
                                      >
                                        <SingleDatePicker
                                          customInputIcon={
                                            <i class="i i-date-range" />
                                          }
                                          noBorder={true}
                                          placeholder="Expiry Date"
                                          disabled={locked}
                                          numberOfMonths={1}
                                          hideKeyboardShortcutsPanel={true}
                                          date={this.state.expiry_date}
                                          focused={
                                            this.state
                                              .expiry_date_mobile_focused
                                          }
                                          onFocusChange={({ focused }) =>
                                            this.setState({
                                              expiry_date_mobile_focused: focused,
                                            })
                                          }
                                          onDateChange={this.pickExpiryDate}
                                          displayFormat="MMM DD, YYYY"
                                          isOutsideRange={this.expiryDateRange}
                                          showClearDate={true}
                                        />
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <FieldArray
                        name="line_items"
                        component={LineItemTable}
                        items={this.props.items}
                        disabled={isDisabled}
                        invoice={invoice}
                        invoiceTotal={invoiceTotal}
                        gstSlabs={gstSlabs}
                        applyTaxes={
                          Boolean(merchantGSTIN) && this.props.state_of_supply
                        }
                      />

                      <div class="row" style={{ marginTop: '40px' }}>
                        <div class="col-md-12">
                          <InlineField
                            formName="newInvoice"
                            name="comment"
                            component={AutoResizeTextarea}
                            class="material-input"
                            placeholder={`${
                              !locked ? 'Add ' : ''
                            }Customer Notes`}
                            rows="1"
                            keepValueInBG={false}
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
                            class="material-input"
                            placeholder={`${
                              !locked ? 'Add ' : ''
                            }Terms and Conditions`}
                            rows="1"
                            keepValueInBG={false}
                            disabled={locked}
                          />
                        </div>
                      </div>

                      <div class="inv__Footer">
                        <div class="inv__Footer__merchantName">
                          {this.state.merchantAltBillingLabel}
                        </div>
                        {isAddressValid(merchantAddress) && (
                          <div class="inv__Footer__merchantAddress">
                            <AddressDisplay address={merchantAddress} />
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>

                <ShowWhen notMyRole="support finance">
                  <div class="col-md-4 col-sm-4" style={{ marginTop: '48px' }}>
                    {!locked && (
                      <div class="inv__cta">
                        <div class="btn-group-vertical">
                          {(isNew || isDraft) && (
                            <AsyncButton
                              type="button"
                              class="btn btn-primary btn-block btn-lg"
                              disabled={
                                this.state.isSaving || this.props.invalid
                              }
                              onClick={handleSubmit(props => {
                                return this.saveAndIssue({
                                  ...props,
                                  ...{ draft: '0' },
                                });
                              })}
                            >
                              <div class="row inv__optiongroupbutton">
                                <div class="col-xs-4">
                                  <i class="i i-check" />
                                </div>
                                <div class="col-xs-8">Finalize and Issue</div>
                              </div>
                            </AsyncButton>
                          )}

                          {isIssued && (
                            <AsyncButton
                              type="button"
                              class="btn btn-primary btn-block btn-lg"
                              disabled={this.state.isSaving}
                              onClick={handleSubmit(this.resendInvoice)}
                            >
                              <div class="row inv__optiongroupbutton">
                                <div class="col-xs-4">
                                  <i class="i i-send" />
                                </div>
                                <div class="col-xs-8">Resend Invoice</div>
                              </div>
                            </AsyncButton>
                          )}
                        </div>
                        <div class="btn-group-vertical">
                          {!locked && (
                            <AsyncButton
                              type="button"
                              class="btn btn-default btn-block btn-lg"
                              text="Save Invoice"
                              pendingText="Saving..."
                              disabled={
                                this.state.isSaving || this.props.invalid
                              }
                              onClick={handleSubmit(props => {
                                return this.save({
                                  ...props,
                                  ...{ draft: isIssued ? '0' : '1' },
                                });
                              })}
                            >
                              <div class="row inv__optiongroupbutton">
                                <div class="col-xs-4">
                                  <i class="i i-save" />
                                </div>
                                <div class="col-xs-8">Save Invoice</div>
                              </div>
                            </AsyncButton>
                          )}
                          {(isNew || isDraft) && (
                            <button
                              type="button"
                              class="btn btn-default btn-block btn-lg"
                              onClick={this.deleteInvoice}
                              disabled={this.state.isSaving}
                            >
                              <div class="row inv__optiongroupbutton">
                                <div class="col-xs-4">
                                  <i class="i i-close" />
                                </div>
                                <div class="col-xs-8">Delete Invoice</div>
                              </div>
                            </button>
                          )}
                          {isIssued && (
                            <button
                              type="button"
                              class="btn btn-default btn-block btn-lg"
                              onClick={this.cancelInvoice}
                              disabled={this.state.isSaving}
                            >
                              <div class="row inv__optiongroupbutton">
                                <div class="col-xs-4">
                                  <i class="i i-close" />
                                </div>
                                <div class="col-xs-8">Cancel Invoice</div>
                              </div>
                            </button>
                          )}
                        </div>
                        <div class="btn-group-vertical inv__actionbutton">
                          <p>Settings</p>
                          <label
                            class="btn btn-default btn-block btn-lg"
                            for="partial_payment"
                          >
                            <div class="row">
                              <div class="col-xs-10">
                                <h3>Enable Partial Payments</h3>
                                <p>Allow accepting multiple payments</p>
                              </div>
                              <div
                                class="col-xs-2"
                                onClick={() =>
                                  track({
                                    eventAction: 'Enable - Partial Payment',
                                  })
                                }
                              >
                                <div class="custom-checkbox">
                                  <Field
                                    name="partial_payment"
                                    id="partial_payment"
                                    component="input"
                                    type="checkbox"
                                    disabled={locked}
                                    class="Input-el"
                                  />
                                  <div class="Input-checkbox" />
                                </div>
                              </div>
                            </div>
                          </label>
                          {showEditInvoiceLabelOption && (
                            <button
                              class="btn btn-default btn-block btn-lg"
                              onClick={this.showEditInvoiceLabelModal}
                              type="button"
                            >
                              <div class="row">
                                <div class="col-xs-10">
                                  <h3>Change Invoice Label</h3>
                                  <p>
                                    Invoices will be issued under this label
                                  </p>
                                </div>
                                <i
                                  class="col-xs-2 i i-arrow-forward"
                                  style={{ marginTop: '0.5em' }}
                                />
                              </div>
                            </button>
                          )}
                        </div>
                      </div>
                    )}

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
          </div>
        )}
      </div>
    );
  }
}
