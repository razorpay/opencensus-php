import { Fragment, Component } from 'react';
import PropTypes from 'prop-types';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import AsyncButton from 'react-async-button';
import RTracking from 'react-tracking';
import moment from 'moment';
import { withI18Service } from 'common/i18';
import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import AutoResizeTextarea from 'common/ui/Forms/AutoResizeTextarea';
import { TypeAhead, PowerSelect } from 'react-power-select';

import Spinner from 'common/ui/Spinner';
import InlineField from 'common/ui/Forms/InlineField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  findBy,
  getKeysSeparatedByPipe,
  getGSTSlabs,
  capitalize,
  isAddressValid,
  calculateTax,
  getURLQueryParams,
  titleCase,
  classList,
} from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';

import LineItemsList from 'merchant/views/Invoices/Invoices/components/LineItems/List';
import CustomerCreation from 'merchant/views/Customers/New';
import IssueConfirmModal from 'merchant/views/Invoices/Invoices/components/IssueConfirmModal';
import AddInternalNoteModal from 'merchant/views/Invoices/Invoices/components/AddInternalNoteModal';
import InvoiceBreadcrumbNav from 'merchant/views/Invoices/Invoices/components/InvoiceBreadcrumbNav';
import InvoiceInfo from 'merchant/views/Invoices/Invoices/components//InvoiceInfo';
import InvoiceNotes from 'merchant/views/Invoices/Invoices/components/InvoiceNotes';
import InvoiceLogo from 'merchant/views/Invoices/Invoices/components/InvoiceLogo';
import {
  fetchCustomersApi,
  fetchCustomersForAutocomplete,
  fetchCustomerAddresses,
  appendCustomerInList,
} from 'merchant/reducers/customers';
import { fetchItemsForAutocomplete } from 'merchant/reducers/items';
import { saveInvoice, deleteInvoice } from 'merchant/reducers/invoices/list';
import { fetchStates } from 'merchant/reducers/states';
import { fetchGSTTaxes } from 'merchant/reducers/taxes';
import * as InvoiceActions from 'merchant/reducers/invoices/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { SingleDatePicker } from 'react-dates';
import BillingAddress from 'merchant/views/Invoices/Invoices/components/BillingAddress';
import ShippingAddress from 'merchant/views/Invoices/Invoices/components/ShippingAddress';
import AddressSelectionModal from 'merchant/views/Invoices/Invoices/components/AddressSelectionModal/index';
import EditInvoiceLabelModal from 'merchant/views/Invoices/Invoices/components/EditInvoiceLabel';
import AddressDisplay from 'merchant/views/Invoices/Invoices/components/AddressDisplay';
import { states } from 'merchant/helpers/data';
import InvoicesConfiguration from 'merchant/views/Invoices/Invoices/components/InvoicesConfiguration';
import { luminateRow } from 'merchant/reducers/app';
import {
  track,
  trackClickDuplicateInvoice,
  trackSaveDuplicateInvoice,
  trackChangeCurrencySettings,
  trackSelectBillingAddress,
  trackSelectShippingAddress,
} from 'merchant/views/Invoices/ga';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import PickCurrency from 'merchant/views/Invoices/Invoices/components/PickCurrency';
import debounce from 'common/utils/debounce';
import { removeTaxForNonINRItems, TAX_DIVISOR } from './helpers';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { isExperimentEnabled } from 'common/splitz/utils';
import { withSplitzService } from 'common/splitz';

function validate(values) {
  const errors = {
    line_items: [],
  };
  const lineItems = (values.line_items || []).filter(
    (item) => !!((item.item_id && item.item_id !== 'NULL') || item.id || item.name),
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

// eslint-disable-next-line react/no-unsafe
@withI18Service
@connect(
  (state) => {
    const customers = state.customers;
    return {
      session: state.session,
      customers: state.customers,
      items: state.items.items,
      customer: findBy(customers.items, 'id', selector(state, 'customer.id')),
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
    appendCustomerInList,
    saveInvoice,
    deleteInvoice,
    fetchStates,
    fetchGSTTaxes,
    ...InvoiceActions,
    ...ModalActions,
    ...NotificationsActions,
  },
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
@RTracking(() => window.rzpQ.component('InvoicesNewContainer'))
class InvoicesNewContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  static defaultProps = {
    customer: {},
  };

  constructor(props) {
    // eslint-disable-next-line prefer-rest-params
    super(...arguments);
    const issue_date = moment().startOf('day');
    this.state = {
      status: {},
      issue_date,
      today: issue_date,
      selectedCustomerDisplay: null,
      isFetchingAddresses: false,
      invoiceCurrency: this.props.invoice.currency || props.session.user.merchant.currency,
    };
  }

  trackCreateInvoice = (event, options) => {
    if (!event) return;

    this.props.tracking.trackEvent(
      window.rzpQ.invoice().interaction(`invoice.create.${event}`, {
        ...options,
        status: this.props.invoice.status,
        clone: this.isIntentDuplicate,
      }),
    );
  };

  trackUpdateInvoice = (event, options) => {
    if (!event) return;

    this.props.tracking.trackEvent(
      window.rzpQ.invoice().interaction(`invoice.update.${event}`, {
        ...options,
        status: this.props.invoice.status,
        clone: this.isIntentDuplicate,
      }),
    );
  };

  fetchIfIntentDuplicate(invoiceId) {
    return this.props
      .fetchInvoice(invoiceId)
      .then((data) => {
        this.isIntentDuplicate = true;

        let expire_by = data.expire_by && moment(data.expire_by * 1000);

        // If null or is before current time
        if (!expire_by || expire_by.diff(moment()) < 0) {
          expire_by = '';
        } else {
          expire_by = data.expire_by;
        }

        // Resetting values to initial state
        // data.draft = '0'; // Not sure where it's being consumed but re-initializing same as redux form
        data.date = Math.ceil(new Date().getTime() / 1000); // Overriding invoice.date to initial date in redux form
        data.expire_by = expire_by;
        data.id = '';
        data.receipt = '';
        data.status = 'draft';

        // setting string values to empty string if null to avoid error from backend on save
        data.description = data.description || '';
        data.comment = data.comment || '';
        data.terms = data.terms || '';

        //removing ids from line_items
        (data.line_items || []).forEach((line_item) => {
          if (line_item.id) {
            delete line_item.id;
          }
        });

        return data;
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  }

  isPaymentLink(invoice) {
    if (invoice.type !== 'link') {
      return false;
    }

    this.props.history.replace('/paymentlinks');
    this.props.history.replace(`/paymentlinks/${invoice.id}`);
    return true;
  }

  /**
   * Initializes the Invoice.
   * @param {Invoice} invoice the invoice object
   */
  _initialize(invoice) {
    this.props.initialize(invoice);
    invoice.customer &&
      invoice.customer.id &&
      this.props.appendCustomerInList(invoice.customer_details);
    // Set issue date.
    if (invoice.date) {
      this.pickIssueDate(moment(invoice.date * 1000), false);
    }

    // Set expiry date.
    if (invoice.expire_by) {
      this.pickExpiryDate(moment(invoice.expire_by * 1000), false);
    }

    // Set Customer.
    invoice.customer && invoice.customer.id && this.setCustomerData(invoice.customer);

    // Set State of Supply
    if (invoice.supply_state_code && this.state.states) {
      this.changeStateOfSupply({
        option: this.findStateByCode(invoice.supply_state_code, this.state.states),
      });
    }

    // Refresh merchant info.
    setTimeout(() => this.getMerchantInfo());
  }

  setCustomerData(customerDetails) {
    const customer =
      this.props.customers.items &&
      this.props.customers.items.find((c) => c.id == customerDetails.id);

    if (customer) {
      this.setState({
        selectedCustomerDisplay: customer,
      });

      const billingAddress = customerDetails.billing_address_id;
      const shippingAddress = customerDetails.shipping_address_id;

      this.onSelectCustomer(customer, billingAddress, shippingAddress, false);
    }
  }

  UNSAFE_componentWillUpdate(nextProps) {
    const curSearchQuery = getURLQueryParams(this.props.location.search);
    const nextSearchQuery = getURLQueryParams(nextProps.location.search);

    if (curSearchQuery.duplicate_id !== nextSearchQuery.duplicate_id) {
      this.initInvoicePage(nextProps);
    }
  }

  UNSAFE_componentWillMount() {
    this.initInvoicePage();
  }

  initInvoicePage(props) {
    let promises = [];
    let invoiceDataFromFetch;
    props = props || this.props;
    const invoiceId = props.params.id;
    const searchQuery = getURLQueryParams(props.location.search);
    this.isIntentDuplicate = false;
    const { abExperiments } = this.props.splitz || {
      abExperiments: {},
    };
    const isCreateFlowUXOptimizationEnabled = isExperimentEnabled(
      abExperiments?.inv_create_flow_ux,
    );

    if (invoiceId) {
      promises.push(
        this.props.fetchInvoice(invoiceId).then((invoice) => {
          if (this.isPaymentLink(invoice)) {
            return null;
          }

          if (invoice.partial_payment) {
            this.props.fetchInvoicePayments(invoiceId);
          }
          return invoice;
        }),
      );
    } else if (searchQuery.duplicate_id) {
      promises.push(this.fetchIfIntentDuplicate(searchQuery.duplicate_id));
    } else {
      props.initializeInvoice();

      if (props.session.user.isInttCurrenciesEnabled) {
        this.openInvoiceCurrencyChangeModal({
          showCross: false,
          currency: props.session.user.merchant.currency,
        });
      }
    }

    /*
      - Fetching customers parallely and not waiting for the API to complete. Loads the invoice page faster and shows loader on specific dropdown instead of blocking the whole render
      - { search_hits: 1 } params the api directly hit elastic search for faster results
      - initialising customer data on success if other APIs complete  before by keeping variable invoiceDataFromFetch as reference
    */
    if (isCreateFlowUXOptimizationEnabled) {
      props.fetchCustomersForAutocomplete({ search_hits: 1 }).then(() => {
        if (invoiceDataFromFetch?.customer) {
          this.setCustomerData(invoiceDataFromFetch.customer);
        }
      });
    }

    promises = [
      !isCreateFlowUXOptimizationEnabled && props.fetchCustomersForAutocomplete(),
      props.fetchItemsForAutocomplete({
        type: 'invoice',
        'expand[]': 'tax',
      }),
      props.fetchStates(),
      props.fetchGSTTaxes(),
      ...promises,
    ];

    this.setState({
      isLoading: true,
    });

    Promise.all(promises)
      .then(([, , _states, gst, invoice]) => {
        if (invoice) {
          this._initialize(invoice);
          invoiceDataFromFetch = invoice;
        }

        const statesList = _states && _states.data && _states.data.items;

        this.setState({
          isLoading: false,
          gst: gst && gst.data,
          states: statesList,
          invoiceCurrency: (invoice && invoice.currency) || props.session.user.merchant.currency,
        });

        // Set state of supply.
        if (statesList && statesList.length > 0 && this.props.supply_state_code) {
          this.changeStateOfSupply({
            option: this.findStateByCode(this.props.supply_state_code, statesList),
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

  UNSAFE_componentWillReceiveProps(nextProps) {
    const invoiceId = this.props.params.id;
    const nextInvoiceId = nextProps.params.id;

    if (invoiceId !== nextInvoiceId) {
      this.props.closeModal();
      this.initInvoicePage(nextProps);
    }
  }

  getMerchantInfo() {
    const user = this.props.session.user;
    const logoUrl = this.props.config.logo_url;
    let gstin = user.gstin;
    const cin = user.company_cin;

    const {
      config: { invoice_label_field },
    } = this.props;
    let merchantAltBillingLabel = user.business_name || user.business_dba;

    if (invoice_label_field && user[invoice_label_field]) {
      merchantAltBillingLabel = user[invoice_label_field];
    }

    // Set label and GSTIN from the invoice.
    if (this.props.invoice && this.props.invoice.status && this.props.invoice.status !== 'draft') {
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
      merchantLogoUrl: logoUrl,
      merchantAltBillingLabel,
      merchantGSTIN: gstin,
      merchantCIN: cin,
    });
  }

  calculateItemsSubTotal() {
    const lineItems = this.props.invoice_line_items || [];

    // Apply taxes only if the merchant has a GSTIN and state of supply is selected;
    const user = this.props.session.user;
    const merchantGstin = user.gstin || user.p_gstin;

    const applyTaxes = Boolean(merchantGstin) && Boolean(this.props.state_of_supply);

    // Get the cost of items without considering the tax on tax_exclusive items.
    const subtotal = lineItems
      .reduce((total, line_item) => {
        const totalAmt = Number(line_item.quantity) * Number(line_item.amountInINR);

        return total + totalAmt;
      }, 0)
      .toFixed(2);

    // Get the total tax applicable on all items.
    let totalTax = 0.0;
    if (applyTaxes) {
      totalTax = lineItems
        .reduce((total, line_item) => {
          const totalAmt = Number(line_item.quantity) * Number(line_item.amountInINR);

          // Add taxes
          let tax = 0;
          let cess = 0;

          if (line_item.tax_rate) {
            tax = calculateTax(totalAmt, line_item.tax_rate / 100, line_item.tax_inclusive);
          }

          if (applyTaxes && line_item.cess) {
            cess = calculateTax(totalAmt, line_item.cess / TAX_DIVISOR, line_item.tax_inclusive);
          }

          return total + cess + tax;
        }, 0)
        .toFixed(2);
    }

    const total = lineItems
      .reduce((_total, line_item) => {
        const totalAmt = Number(line_item.quantity) * Number(line_item.amountInINR);

        let tax = 0;
        let cess = 0;

        // Add taxes
        if (applyTaxes && !line_item.tax_inclusive) {
          if (line_item.tax_rate) {
            tax = calculateTax(totalAmt, line_item.tax_rate / 100);
          }

          if (applyTaxes && line_item.cess) {
            cess = calculateTax(totalAmt, line_item.cess / TAX_DIVISOR);
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
   * @param {Customer} customer the customer object
   */
  setCustomerInProps = (customer) => {
    this.props.change('customer.id', customer.id);
    this.props.change('customer.name', customer.name);
    this.props.change('customer.contact', customer.contact);
    this.props.change('customer.email', customer.email);
    this.props.change('customer.gstin', customer.gstin);
  };

  /**
   * Returns a closure method to select customers.
   * @param {Boolean} updateAddress Whether or not addresses should be updated (an API call should be made to fetch addresses)
   * @param {Boolean} selectShippingAddress Whether or not to select shipping address.
   *
   * @return {Function} Returns a closure method to select customers.
   *    @param {Customer} customer Selected/Created customer.
   *    @param {Boolean} shippingSameAsBilling whether or not shipping address to be used is the same as billing address. To be used for new customers.
   */
  selectCustomerAndCloseModal =
    (updateAddress = false, selectShippingAddress = false) =>
    (customer, shippingSameAsBilling = false) => {
      // Update for TypeAhead
      this.setState({
        selectedCustomerDisplay: customer,
      });

      // Update in Redux form
      this.setCustomerInProps(customer);
      this.props.closeModal();

      if (updateAddress) {
        this.fetchCustomerAddresses(
          customer.id,
          undefined,
          undefined,
          selectShippingAddress,
          shippingSameAsBilling,
        );
      }

      track({
        eventAction: `Close Form - ${updateAddress ? 'Add' : 'Edit'} Customer`,
      });

      this.trackCreateInvoice('newcustomer');
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
   * @returns {Function} Returns a function which fetches the customers address
   */
  fetchCustomerAddresses = (
    customerID,
    billingAddressID,
    shippingAddressID,
    selectShippingAddress = false,
    shippingSameAsBilling = false,
    autoselectPlaceOfSupply = true,
  ) => {
    // Set loading state.
    this.setState({
      isFetchingAddresses: true,
    });

    return this.props
      .fetchCustomerAddresses({
        id: customerID,
      })
      .then((response) => {
        if (!response.success) return;

        // Set shipping and billing addresses based on their types.
        const addresses = response.data.items;
        let selected_billing, selected_shipping;

        // If a billing address ID is given, try to set it as selected_billing.
        if (billingAddressID) {
          selected_billing = addresses.find((address) => address.id == billingAddressID);
        }

        // If a shipping address ID is given, try to set it as selected_shipping.
        if (shippingAddressID) {
          selected_shipping = addresses.find((address) => address.id == shippingAddressID);
        }

        if (billingAddressID !== null) {
          // Select the addresses which have primary=true
          if (!selected_billing) {
            selected_billing = addresses.find((address) => address.type === 'billing_address');
          }
        }

        if (shippingAddressID !== null) {
          if (!selected_shipping && shippingSameAsBilling) {
            selected_shipping = selected_billing;
          }

          if (!selected_shipping && selectShippingAddress) {
            selected_shipping = addresses.find((address) => address.type === 'shipping_address');
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
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error.errors,
        });
        throw error;
      });
  };

  setInvoiceCurrency = (newCurrency) => {
    this.setState({
      invoiceCurrency: newCurrency,
    });

    this.trackCreateInvoice('continue');

    // Show only for first time user
    if (!this.props.invoice.id && this.props.config.invoice_label_field === null) {
      this.setState({
        highlightCurrencyChangeCTA: true,
      });

      const el = document.getElementById('change-currency-cta');
      el && el.querySelector('.rzp-popover').classList.add('show');

      setTimeout((_) => {
        this.setState({ highlightCurrencyChangeCTA: false });
        el && el.querySelector('.rzp-popover').classList.remove('show');
      }, 4000);
    }
  };

  openInvoiceCurrencyChangeModal = ({ showCross = true, currency }) => {
    const invoiceCurrency = currency || this.state.invoiceCurrency;

    trackChangeCurrencySettings();

    this.props.openModal({
      size: 'small',
      component: (
        <PickCurrency
          currency={invoiceCurrency}
          onSave={this.setInvoiceCurrency}
          closeModal={this.props.closeModal}
          showCross={showCross}
          onOpen={() => {
            this.trackCreateInvoice('currency_drop');
          }}
          onChange={() => {
            this.trackCreateInvoice('currency_browse');
          }}
        />
      ),
    });
  };

  quickCreateCustomer = ({ searchTerm = '', closeSelectCustomerDropdown }) => {
    closeSelectCustomerDropdown();

    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Create Customer"
          onSave={this.selectCustomerAndCloseModal(true, true)}
          customer={{
            name: searchTerm,
          }}
          showGSTN={this.state.invoiceCurrency === 'INR'}
          onBlur={(e) => {
            this.trackCreateInvoice(`newcustomer_${e.target.name}`);
          }}
          onCloseClick={() => {
            this.trackCreateInvoice(`newcustomer_leave`);
          }}
        />
      ),
    });
  };

  /**
   * Shows the Edit Customer modal.
   * @param {DOMEvent} e the dom event object
   */
  quickEditCustomer = (e) => {
    e && e.preventDefault();

    this.trackCreateInvoice('edit_customer');

    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Update Customer"
          onSave={this.selectCustomerAndCloseModal()}
          customer={this.props.customer}
          showGSTN={this.state.invoiceCurrency === 'INR'}
          onBlur={(event) => {
            this.trackCreateInvoice(`edit_customer_${event.target.name}`);
          }}
          onCloseClick={() => {
            this.trackCreateInvoice(`edit_customer_leave`);
          }}
        />
      ),
    });
  };

  /**
   * Shows the onboarding modal.
   */
  showInvoicesConfigurationModal = () => {
    const onStart = () => {
      track({
        eventAction: 'Click - Start Creating Invoices',
      });
      this.getMerchantInfo();

      if (this.props.session.user.isInttCurrenciesEnabled) {
        this.openInvoiceCurrencyChangeModal({ showCross: false });
      }
    };

    const onCloseClick = () => {
      this.props.closeModal();
      this.navigateToList();
    };

    this.props.openModal({
      size: 'regular',
      component: (
        <InvoicesConfiguration
          merchant={this.props.session.user}
          invoiceLabelField={this.props.config.invoice_label_field}
          onStart={onStart}
          onCloseClick={onCloseClick}
        />
      ),
    });
  };

  /**
   * Method to set the selected billing address.
   * @param {Object} address the address object
   * @param {Boolean} autoselectPlaceOfSupply Whether or not to autoselect place of supply.
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
      },
    );

    // Update billing address ID.
    this.props.change('customer.billing_address_id', address ? address.id : null);
  };

  /**
   * Method to set the selected shipping address.
   * @param {Object} address the address object
   */
  selectShippingAddress = (address) => {
    this.setState({
      selectedShippingAddress: address,
    });

    // Update shipping address ID.
    this.props.change('customer.shipping_address_id', address ? address.id : null);
  };

  /**
   * Shows the Edit Invoice Label modal.
   * @param {DOMEvent} e the dom event object
   */
  showEditInvoiceLabelModal = (e) => {
    e.preventDefault();

    track({
      eventAction: 'Change - Invoice Label',
    });

    const {
      session: { user },
      config: { invoice_label_field },
    } = this.props;

    const onSave = () => {
      this.props.closeModal();

      this.trackCreateInvoice('save_label');

      this.getMerchantInfo();
    };

    this.trackCreateInvoice('change_label');

    this.props.openModal({
      size: 'small',
      component: (
        <EditInvoiceLabelModal merchant={user} current={invoice_label_field} onSave={onSave} />
      ),
    });
  };

  /**
   * Returns a handler to show Address Selection Modal.
   * @param {String} type One of "billing" and "shipping".
   * @returns {void}
   */
  showSelectAddressModal =
    (type = 'billing') =>
    (e) => {
      e.preventDefault();

      track({
        eventAction: 'Change - Address',
        eventLabel: capitalize(type),
      });

      type = type.toLowerCase();

      const { selectedBillingAddress, selectedShippingAddress, addresses = [] } = this.state;

      const { customer } = this.props;

      // Return if the customer is not yet selected.
      if (!customer) return;

      /**
       * Handler for when an address is created.
       * @param {Object} address the address object
       */
      const onSave = (address) => {
        // Select address.
        if (type === 'billing') {
          this.trackCreateInvoice('billing_save');

          this.selectBillingAddress(address);
        } else {
          this.selectShippingAddress(address);

          this.trackCreateInvoice('shipping_save');
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
            isInttCurrenciesEnabled={this.props.session.user.isInttCurrenciesEnabled}
            selected={type === 'billing' ? selectedBillingAddress : selectedShippingAddress}
            onSelect={type === 'billing' ? this.selectBillingAddress : this.selectShippingAddress}
            onSave={onSave}
            addressType={type}
            trackSelectCountry={(...args) => {
              if (type === 'billing') {
                trackSelectBillingAddress(...args);
              } else {
                trackSelectShippingAddress(...args);
              }

              this.trackCreateInvoice(`${type === 'billing' ? 'billing' : 'shipping'}_county`);
            }}
            onBlur={(event) => {
              this.trackCreateInvoice(
                `${type === 'billing' ? 'billing' : 'shipping'}_${event.target.value}`,
              );
            }}
            onClickClose={() => {
              this.trackCreateInvoice(`${type === 'billing' ? 'billing' : 'shipping'}_leave`);
            }}
            trackAddressSelection={() => {
              this.trackCreateInvoice(`${type === 'billing' ? 'billing' : 'shipping'}_leave`);
            }}
          />
        ),
      });

      this.trackCreateInvoice('billing');
    };

  /**
   * Prepares props to be saved.
   * Updates props in place and also returns them.
   * @param {Object} props props object
   * @return {Object} returns modified props
   */
  prepareForSave = (props) => {
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
      props.expire_by = moment.unix(props.expire_by).endOf('day').unix();
    }

    if (this.state.invoiceCurrency !== 'INR') {
      delete props.supply_state_code;
    }

    return props;
  };

  _save(props) {
    props = this.prepareForSave(props);

    this.setState({
      isSaving: true,
    });

    return this.props
      .saveInvoice(
        props,
        {
          'Content-Type': 'application/json',
        },
        this.isIntentDuplicate,
      )
      .then((invoice) => {
        this.setState({
          isSaving: false,
        });
        this.props.initialize(invoice);

        this.trackCreateInvoice('save');
        return invoice;
      })
      .catch((error) => {
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

  save = (props) => {
    if (this.isIntentDuplicate) {
      trackSaveDuplicateInvoice();
    }

    props = removeTaxForNonINRItems(props, this.state.invoiceCurrency);

    return this._save(props).then((invoice) => {
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

    return this.showIssueConfirmModal((notifyProps) => {
      const updatedProps = removeTaxForNonINRItems(
        {
          ...props,
          ...notifyProps,
        },
        this.state.invoiceCurrency,
      );

      return this._save(updatedProps).then((invoice) => {
        track({
          eventAction: 'Issue - Invoice',
          eventLabel: getKeysSeparatedByPipe(props),
        });
        selfServeTrackSuccess({
          selfServeAction: 'New Invoice Created',
          page: 'Invoices',
          screen: 'Invoices',
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

  resendInvoice = (props) => {
    this.showIssueConfirmModal((notifyProps) => {
      // Update invoice and then resend.

      return this._save(props)
        .then(() => {
          const promises = [];

          if (notifyProps.email_notify) {
            promises.push(this.props.notifyCustomer(props, 'email'));
          }
          if (notifyProps.sms_notify) {
            promises.push(this.props.notifyCustomer(props, 'sms'));
          }

          return Promise.all(promises)
            .then(() => {
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
          onIssue={(notifyProps) => {
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

    const invoice = this.props.invoice;
    this.context.confirm({
      header: 'Delete Invoice?',
      message: () => (
        <div class="text-semi-muted">
          <p>The Invoice will be deleted. There is no coming back!. Are you sure?</p>
          <div>
            If you have added any item or customer, you can still use them in other invoices.
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

    return null;
  };

  cancelInvoice = () => {
    const invoice = this.props.invoice;
    this.context.confirm({
      header: 'Cancel Invoice?',
      message: () => (
        <div class="text-semi-muted">
          <p>The Invoice will be cancelled and the customer will not be able to pay for it.</p>
        </div>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .cancelInvoice(invoice)
          .then((_invoice) => {
            track({
              eventAction: 'Cancel - Invoice',
              eventLabel: `invoice_id=${_invoice.id}`,
            });
            this.props.initialize(_invoice);
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

  addInternalNote = () => {
    const invoice = this.props.invoice;
    this.props.openModal({
      size: 'small',
      component: (
        <AddInternalNoteModal
          onSave={(note) => {
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
   * @param {Array} _states Array of state objects (this.state.states)
   * @return {Object} state object
   */
  findStateByCode = (code, _states) => _states.find((o) => o.code === code);

  /**
   * Finds a state by it's name.
   * @param {String} name State name
   * @param {Array} _states Array of state objects (this.state.states)
   * @return {Object} state object
   */
  findStateByName = (name, _states) => _states.find((o) => o.name === name);

  /**
   * Sets Issue Date.
   * @param {MomentObj} date moment date object
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
      const diff = date.diff(expiry_date, 'd', true);
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

    this.trackCreateInvoice('issue_date');
  };

  /**
   * Sets Expiry Date
   * @param {MomentObj} date moment date object
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

    this.trackCreateInvoice('expire_date');
  };

  /**
   * Returns false for all the dates after today.
   * @param {MomentObj} date moment date object
   * @return {Boolean} returns false for all the dates after today.
   */
  issueDateRange = (date) => {
    date = date.startOf('day');

    return this.isDateAfter(date, this.state.today);
  };

  /**
   * Returns false for all the dates before today.
   * @param {MomentObj} date moment date object
   * @return {Boolean} returns false for all the dates before today.
   */
  expiryDateRange = (date) => {
    date = date.startOf('day');

    return this.isDateBefore(date, this.state.today);
  };

  componentWillUnmount() {
    this.props.closeModal();

    let action;
    if (!this.props.params.id) {
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
    if (!this.props.params.id) {
      action = 'Open Form - New Invoice';
    } else {
      action = 'Open Details - Invoice';
    }
    track({
      eventAction: action,
    });

    /**
     * Show configuration modal if invoice_label_field is null or GSTIN is empty.
     */
    const { invoice_label_field } = this.props.config;

    if (invoice_label_field === null) {
      this.showInvoicesConfigurationModal();
    }

    this.trackCreateInvoice('start');
  }

  /**
   * Methods to compare two dates.
   * @param {Moment} base Base date
   * @param {Moment} d Date to compare
   * @return {Boolean} boolean after comparing dates
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
   * @param {State} stateOfSupply State of Supply
   * @returns {void}
   */
  updateGSTSlabs = (stateOfSupply) => {
    const { merchantGSTIN, gst } = this.state;

    // If the merchant doesn't have a GSTIN, stop.
    if (!merchantGSTIN) {
      return null;
    }

    // If the state of supply is empty, clear slabs.
    if (!stateOfSupply) {
      return this.setState({
        gstSlabs: null,
      });
    }

    // Get the merchant's state.
    const merchantState = this.findStateByCode(merchantGSTIN.slice(0, 2), this.state.states);

    // Get the applicable groups and slabs and set them in state.
    const gstSlabs = getGSTSlabs(
      gst.gst_tax_slabs_v2, // [0, 500, 1200, ...]
      merchantState.code, // "29"
      stateOfSupply.code, // "29"
      gst.gst_tax_id_map_v2, // {CGST_0: "tax_1234", CGST_250: "tax_3456", ...}
      stateOfSupply.is_ut || merchantState.is_ut, // Whether or not any of the states is a Union Territory
    );
    this.setState({
      gstSlabs,
    });

    return null;
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
    autoselectPlaceOfSupply,
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
      autoselectPlaceOfSupply,
    );

    this.trackCreateInvoice('existing_customer');
  };

  showGSTModal = () => {
    return this.props.openModal({
      size: 'small',
      component: <AddGST reloadAfterSave={true} />,
    });
  };

  onBlur = (event) => {
    this.trackCreateInvoice(event.target.name);
  };

  handleSelectCustomer = ({ option }) => {
    /* Info : we are doing this `appendCustomerInList` here because of a bug , when we select a customer the previous logic
    search for the customer in the cutomers list hence return an undefined. By doing this we add that customer in customers list
    */

    this.props.appendCustomerInList(option);
    this.typeAheadSkin.classList.remove('hide');

    if (option) {
      this.props.change('customer.id', option.id);
    } else {
      this.props.change('customer.id', null);
      this.props.untouch('customer.id');
    }

    // For display purpose only in TypeAhead
    this.setState({ selectedCustomerDisplay: option });

    const customerDetails = option;
    if (customerDetails) {
      const customer =
        this.props.customers.items &&
        this.props.customers.items.find((c) => c.id == customerDetails.id);

      if (customer) {
        const billingAddress = customerDetails.billing_address_id;
        const shippingAddress = customerDetails.shipping_address_id;
        this.onSelectCustomer(customer, billingAddress, shippingAddress, false);
      }
    }
  };

  searchInCustomers(val) {
    fetchCustomersApi({ q: val, search_hits: 1 })
      .then((resp) => {
        let customersList = null;
        if (resp.data && resp.data.items && resp.data.items.length) {
          customersList = resp.data.items;
        }

        this.setState({ customersList });
      })
      .catch(() => {
        this.setState({ customersList: null });
      });
  }

  debounce_searchInCustomers = debounce(this.searchInCustomers.bind(this), 50);

  handleKeyDown = (e) => {
    const target = e.target;

    setTimeout(() => {
      const val = target.value;

      if (val.length < 2) {
        return;
      }

      this.debounce_searchInCustomers(val);
    }, 5);
  };

  render() {
    const {
      handleSubmit,
      customer,
      invoice,
      session: { user, org },
      i18: { isConfigTagEnabled },
    } = this.props;
    const { selectedCustomerDisplay } = this.state;

    const hasCustomerSelected = customer && customer.id;

    const isTestMode = this.props.session.mode === 'test';
    const isNew = !invoice.id;
    const status = invoice.status;
    const isDraft = status === 'draft';
    const isIssued = status === 'issued';
    const isPaid = status === 'paid';
    const isPartiallyPaid = status === 'partially_paid';
    const isCancelled = status === 'cancelled';
    const isExpired = status === 'expired';
    const locked = isPartiallyPaid || isPaid || isExpired || isCancelled;

    const invoiceTotal = this.calculateInvoiceTotal();

    // Merchant Address object used to show the address in footer.
    const merchantAddress = {
      line1: this.props.session.user.business_registered_address,
      city: this.props.session.user.business_registered_city,
      state: states[this.props.session.user.business_registered_state],
      country: 'India',
      zipcode: this.props.session.user.business_registered_pin,
    };

    const {
      gstSlabs,
      merchantGSTIN,
      merchantCIN,
      selectedBillingAddress,
      selectedShippingAddress,
      isFetchingAddresses,
      invoiceCurrency,
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
      selectedBillingAddress && customer && customer.id && !isFetchingAddresses && !isDisabled;
    const areShippingAddressActionsVisible =
      selectedShippingAddress && customer && customer.id && !isFetchingAddresses && !isDisabled;

    const showGstn = invoiceCurrency === 'INR';

    const duplicateInvoiceButton = this.props.invoice.id && !this.props.invoice.subscription_id && (
      <NavLink
        class="btn btn-default btn-block btn-lg"
        to={`/invoices/new?duplicate_id=${invoice.id}`}
        onClick={() => {
          this.trackUpdateInvoice('invoice.update.clone');

          trackClickDuplicateInvoice();
        }}
      >
        <div class="row inv__optiongroupbutton">
          <div class="col-xs-4">
            <i class="i i-copy" />
          </div>
          <div class="col-xs-8">Duplicate Invoice</div>
        </div>
      </NavLink>
    );

    let customersList;

    if (!this.props.customers.loading) {
      customersList = this.state.customersList || this.props.customers.items;
    }

    const customerEmail = customer.email;
    const customerGstin = customer.gstin;
    const customerContact = customer.contact;

    const showCreateGSTEnabledInvoicesOption =
      !merchantGSTIN && (isNew || isDraft) && !isConfigTagEnabled('account.gst');
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

                    <Alert type={this.state.status.type} message={this.state.status.message} />

                    <div class="invoice">
                      {isTestMode && (
                        <div class="alert-sm alert-warning testmode-warning">
                          Invoice is created in <b>Test Mode</b>. Only test payments can be made for
                          this invoice
                        </div>
                      )}
                      <InvoiceLogo
                        logo={this.state.merchantLogoUrl}
                        name={this.state.merchantAltBillingLabel}
                        gstin={showGstn && merchantGSTIN}
                        cin={showGstn && merchantCIN}
                        hideRazorpayDetails={user.isWhiteLabelledOrg}
                        org={org}
                      />

                      <div class="row">
                        <div class="col-md-12">
                          <div class="inv__titlesection">
                            <h3>Invoice #</h3>
                            {locked && !invoice.receipt ? (
                              <InlineField
                                formName="newInvoice"
                                name="id"
                                component="input"
                                class="material-input input-xs"
                                disabled={true}
                                size={30}
                              />
                            ) : (
                              <InlineField
                                formName="newInvoice"
                                name="receipt"
                                component="input"
                                class="material-input"
                                placeholder={`${!locked ? 'Enter ' : ''}Invoice Number`}
                                disabled={locked}
                                keepValueInBG={false}
                                onBlur={this.onBlur}
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
                              onBlur={this.onBlur}
                            />
                          </div>
                        </div>
                      </div>
                      {(invoice.amount_due && invoice.amount_due > 0.0) ||
                      (invoiceTotal && invoiceTotal.total && invoiceTotal.total > 0.0) ? (
                        <div class="row">
                          <div class="col-md-12">
                            <div>
                              <label class="inv__amountduetitle">AMOUNT DUE</label>
                              <h3 class="inv__amountdue">
                                {invoice.amount_due ? (
                                  <Amount
                                    value={invoice.amount_due}
                                    currency={this.state.invoiceCurrency}
                                  />
                                ) : (
                                  <Amount
                                    value={invoiceTotal.total * 100}
                                    currency={this.state.invoiceCurrency}
                                  />
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
                              <div class="custom-select auto-complete-search">
                                <TypeAhead
                                  options={customersList}
                                  disabled={!customersList}
                                  class="ps-in-modal"
                                  searchIndices={['id', 'name', 'email']}
                                  placeholder={`${
                                    !customersList ? 'Loading...' : 'Select a customer'
                                  }`}
                                  showClear={true}
                                  selected={selectedCustomerDisplay}
                                  selectedOptionLabelPath="id"
                                  optionComponent={({ option }) => {
                                    return (
                                      <div class="custom-powerselect-options">
                                        <div>
                                          <b>{titleCase(option.name)}</b> (
                                          {option.code || option.id})
                                        </div>
                                        {option.email}
                                      </div>
                                    );
                                  }}
                                  beforeOptionsComponent={() => <div class="heading">Recent</div>}
                                  afterOptionsComponent={({ select }) => {
                                    return (
                                      <div
                                        class="quick-create"
                                        onClick={() =>
                                          this.quickCreateCustomer({
                                            searchTerm: select.searchTerm,
                                            closeSelectCustomerDropdown: select.actions.close,
                                          })
                                        }
                                      >
                                        <i class="i i-plus" />
                                        <b>Create New Customer</b>
                                      </div>
                                    );
                                  }}
                                  onChange={this.handleSelectCustomer}
                                  onKeyDown={this.handleKeyDown}
                                />
                                <div class="typeAheadSkin" ref={(c) => (this.typeAheadSkin = c)}>
                                  {selectedCustomerDisplay ? (
                                    <div>
                                      {selectedCustomerDisplay.name
                                        ? titleCase(selectedCustomerDisplay.name)
                                        : selectedCustomerDisplay.id}
                                    </div>
                                  ) : null}
                                </div>
                              </div>
                            </div>
                            {hasCustomerSelected && (
                              <div class="inv__customerdetails">
                                {customerContact && <div>{customerContact}</div>}
                                {customerEmail ? <div>{customerEmail}</div> : ''}
                                {customerGstin && showGstn && (
                                  <div>
                                    <span class="tax-heading">GSTIN - </span>
                                    {customerGstin}
                                  </div>
                                )}
                              </div>
                            )}
                            <div>
                              {hasCustomerSelected && !isDisabled && (
                                <button
                                  class="btn btn-link no-padding"
                                  onClick={this.quickEditCustomer}
                                  type="button"
                                >
                                  Edit Customer
                                </button>
                              )}
                            </div>
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
                                    customInputIcon={<i class="i i-date-range" />}
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
                                  <label class="text-uppercase">Expiry Date</label>
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
                                      customInputIcon={<i class="i i-date-range" />}
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
                                        this.state.expiry_date_focused ? 'show-info' : ''
                                      }`}
                                    >
                                      Expiry Date is the date after which the customer will be
                                      unable to pay for this Invoice.
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
                              <BillingAddress
                                isFetchingAddresses={isFetchingAddresses}
                                isDisabled={isDisabled}
                                customer={customer}
                                showSelectAddressModal={this.showSelectAddressModal}
                                areBillingAddressActionsVisible={areBillingAddressActionsVisible}
                                selectBillingAddress={this.selectBillingAddress}
                                selectedBillingAddress={selectedBillingAddress}
                                track={track}
                              />
                              <ShippingAddress
                                isFetchingAddresses={isFetchingAddresses}
                                isDisabled={isDisabled}
                                customer={customer}
                                showSelectAddressModal={this.showSelectAddressModal}
                                areShippingAddressActionsVisible={areShippingAddressActionsVisible}
                                selectShippingAddress={this.selectShippingAddress}
                                selectedShippingAddress={selectedShippingAddress}
                                track={track}
                              />
                              {merchantGSTIN && showGstn && (
                                <div class="inv__place-of-supply-container">
                                  <label class="text-uppercase">Place of Supply</label>
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
                                    <label class="text-uppercase">Issue Date</label>
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
                                        customInputIcon={<i class="i i-date-range" />}
                                        noBorder={true}
                                        disabled={isDisabled}
                                        placeholder="Issue Date"
                                        showClearDate={false}
                                        numberOfMonths={1}
                                        hideKeyboardShortcutsPanel={true}
                                        date={this.state.issue_date}
                                        focused={this.state.issue_date_mobile_focused}
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
                                      <label class="text-uppercase">Expiry Date</label>
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
                                          customInputIcon={<i class="i i-date-range" />}
                                          noBorder={true}
                                          placeholder="Expiry Date"
                                          disabled={locked}
                                          numberOfMonths={1}
                                          hideKeyboardShortcutsPanel={true}
                                          date={this.state.expiry_date}
                                          focused={this.state.expiry_date_mobile_focused}
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
                        component={LineItemsList}
                        items={this.props.items}
                        disabled={isDisabled}
                        invoice={invoice}
                        invoiceCurrency={invoiceCurrency}
                        invoiceTotal={invoiceTotal}
                        gstSlabs={gstSlabs}
                        applyTaxes={
                          Boolean(merchantGSTIN) &&
                          this.props.state_of_supply &&
                          invoiceCurrency === 'INR'
                        }
                        trackLineItem={this.trackCreateInvoice}
                      />

                      <div class="row" style={{ marginTop: '40px' }}>
                        <div class="col-md-12">
                          <label class="text-uppercase">Customer Notes</label>
                          <InlineField
                            formName="newInvoice"
                            name="comment"
                            component={AutoResizeTextarea}
                            class="material-input"
                            placeholder={`${!locked ? 'Add ' : ''}Customer Notes`}
                            rows="1"
                            keepValueInBG={false}
                            disabled={locked}
                          />
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-12">
                          <label class="text-uppercase">Terms and Conditions</label>
                          <InlineField
                            formName="newInvoice"
                            name="terms"
                            component={AutoResizeTextarea}
                            class="material-input"
                            placeholder={`${!locked ? 'Add ' : ''}Terms and Conditions`}
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
                        <ShowWhen
                          additionalCondition={() =>
                            !isConfigTagEnabled('invoices.footer_address') &&
                            isAddressValid(merchantAddress)
                          }
                        >
                          <div class="inv__Footer__merchantAddress">
                            <AddressDisplay address={merchantAddress} />
                          </div>
                        </ShowWhen>
                      </div>
                    </div>
                  </div>
                </div>

                <ShowWhen additionalCondition={(_user) => _user.isAllowedEdit('invoices')}>
                  <div class="col-md-4 col-sm-4 invoices--side">
                    {!locked && (
                      <div class="inv__cta">
                        <div class="btn-group-vertical">
                          {(isNew || isDraft) && (
                            <AsyncButton
                              type="button"
                              class="btn btn-primary btn-block btn-lg"
                              disabled={
                                this.state.isSaving || this.props.invalid || !hasCustomerSelected
                              }
                              onClick={handleSubmit((props) => {
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
                              disabled={this.state.isSaving || this.props.invalid}
                              onClick={handleSubmit((props) => {
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
                          {duplicateInvoiceButton}
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
                          {showCreateGSTEnabledInvoicesOption && (
                            <label class="btn btn-default btn-block btn-lg" for="gst_enabled">
                              <div class="row">
                                <div class="col-xs-10">
                                  <h3>Create GST Enabled Invoices</h3>
                                  <p>Add your GST number</p>
                                </div>
                                <div class="col-xs-2">
                                  <div class="custom-checkbox">
                                    <Field
                                      name="gst_enabled"
                                      id="gst_enabled"
                                      component="input"
                                      type="checkbox"
                                      disabled={locked}
                                      class="Input-el"
                                      checked={!!merchantGSTIN}
                                      onChange={this.showGSTModal}
                                    />
                                    <div class="Input-checkbox" />
                                  </div>
                                </div>
                              </div>
                            </label>
                          )}
                          <label class="btn btn-default btn-block btn-lg" for="partial_payment">
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
                                  <p>Invoices will be issued under this label</p>
                                </div>
                                <i
                                  class="col-xs-2 i i-arrow-forward"
                                  style={{ marginTop: '0.5em' }}
                                />
                              </div>
                            </button>
                          )}
                          {!invoice.id && this.props.session.user.isInttCurrenciesEnabled && (
                            <div
                              id="change-currency-cta"
                              class={classList(
                                'change-currency-cta',
                                this.state.highlightCurrencyChangeCTA && 'highlight',
                              )}
                            >
                              <button
                                class="btn btn-default btn-block btn-lg"
                                onClick={this.openInvoiceCurrencyChangeModal}
                                type="button"
                              >
                                <div class="row">
                                  <div class="col-xs-10">
                                    <h3>Change Currency</h3>
                                    <p>Select different currency</p>
                                  </div>
                                  <i
                                    class="col-xs-2 i i-arrow-forward"
                                    style={{ marginTop: '0.5em' }}
                                  />
                                </div>
                              </button>
                              <Popover theme="dark" align="bottom">
                                <PopoverBody>
                                  Going forward you can change the Invoice currency here
                                </PopoverBody>
                              </Popover>
                            </div>
                          )}
                        </div>
                      </div>
                    )}

                    <ShowWhen
                      additionalCondition={(_user) => locked && _user.isAllowedEdit('invoices')}
                    >
                      <div class="inv__cta">
                        <div class="btn-group-vertical">{duplicateInvoiceButton}</div>
                      </div>
                    </ShowWhen>

                    <InvoiceInfo invoice={invoice} trackUpdateInvoice={this.trackUpdateInvoice} />

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

export default withSplitzService(withRouter(InvoicesNewContainer));
