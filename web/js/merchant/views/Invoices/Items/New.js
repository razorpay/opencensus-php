import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'common/ui/Forms/InputField';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import { required } from 'common/utils/validators';
import * as ItemActions from 'merchant/reducers/items';
import { saveSubscriptionItem } from 'merchant/reducers/subscriptions';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { PowerSelect } from 'react-power-select';
import { fetchTaxes, saveTax, fetchGSTTaxes } from 'merchant/reducers/taxes';
import RadioButton from 'common/ui/Forms/RadioButton';
import { deepCopy } from 'common/utils/immutable';
import Item from 'merchant/models/Item';
import { isTaxOfTypeCess } from 'common/utils/rzp-utils';
import { AmountTooltip } from 'common/ui/Amount';
import Input from 'common/new-ui/Input';
import { classList } from 'common/utils/rzp-utils';
import CheckableItem from './components/CheckableItem';

const selector = formValueSelector('newItem');

/**
 * Deletes or nullifies a key on the object depending upon the flag.
 * @param {Boolean} shouldDelete Deletes if true, nullifies otherwise.
 * @param {Object} obj Object
 * @param {*} key Key on which to act on.
 */
const deleteOrNullify = (shouldDelete, obj, key) => {
  if (!obj || !key) return;

  if (shouldDelete) {
    delete obj[key];
  } else {
    obj[key] = null;
  }
};

/**
 * Validates length of SAC code.
 * @param {String} sac
 * @param {Object} all
 * @return {String}
 */
const sacLengthValidator = (sac, all) => {
  if (
    all &&
    sac &&
    all.hsn_sac_code_type &&
    all.hsn_sac_code_type === 'sac' &&
    sac.length > 8
  ) {
    return 'SAC Code can be at most 8 characters';
  }

  return undefined;
};

@connect(
  state => ({
    taxRate: selector(state, 'tax_rate'),
    taxInclusive: selector(state, 'tax_inclusive'),
    cess: selector(state, 'cess'),
    selected_currency: selector(state, 'currency'),
    user: state.session.user,
  }),
  {
    fetchTaxes,
    saveTax,
    fetchGSTTaxes,
    saveSubscriptionItem,
    ...ItemActions,
    ...ModalActions,
    ...NotificationsActions,
  }
)
@reduxForm({
  form: 'newItem',
  initialValues: {
    hsn_sac_code_type: 'hsn',
    tax_inclusive: '1',
  },
})
export default class AddItem extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  UNSAFE_componentWillMount() {
    let promises = [this.props.fetchTaxes(), this.props.fetchGSTTaxes()];

    this.setState({
      isLoading: true,
    });

    if (this.props.item) {
      this._initialize(this.props.item, this.props.currency); // In GST invoice, creating New item actually has this.props.items = {name: null}
    } else {
      this.props.initialize({ currency: this.props.currency || 'INR' });
    }

    Promise.all(promises)
      .then(([taxes, gst]) => {
        this.setState({
          isLoading: false,
          gst: gst && gst.data,
          taxes: taxes && taxes.data.items,
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.item);
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.item);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    this.updateTaxRadioSection(nextProps);
  }

  /**
   * Initializes the item.
   * Processes taxes and stuff.
   * @param {Item} item
   */
  _initialize(item, currency) {
    item = new Item(deepCopy(item));

    let showCessForm = false;

    if (this.props.showTaxes) {
      // Convert cess
      if (isTaxOfTypeCess(item.tax)) {
        item.cess = `${item.tax.rate / 100.0}`;
        showCessForm = true;
      } else if (item.cess) {
        // Else if this is being edited using a Line Item
        item.cess = item.cess / 100.0;
        showCessForm = true;
      }

      // Convert tax rate.
      if (item.tax_rate) {
        item.tax_rate = `${item.tax_rate / 100.0}%`;
      }

      // Set tax inclusive
      if (item.tax_inclusive) {
        item.tax_inclusive = '1';
      } else if (item.tax_inclusive === false) {
        item.tax_inclusive = '0';
      } else {
        item.tax_inclusive = '1';
      }

      // Set HSN, SAC things.
      if (item.hsn_code || item.sac_code) {
        if (item.hsn_code) {
          item.hsn_sac_code_type = 'hsn';
        } else if (item.sac_code) {
          item.hsn_sac_code_type = 'sac';
        }
        item.hsn_sac_code = item.hsn_code || item.sac_code;
      } else {
        item.hsn_sac_code_type = 'hsn';
      }
    }

    // Initialize and set state.
    this.props.initialize({
      ...item,
      currency: this.props.currency,
    });

    this.setState({
      showCessForm,
      editingItem: true,
    });
  }

  /**
   * Method to get a Component for Tax Rate list.
   * @return {Component}
   */
  getTaxRateComponent = ({ option }) => (
    <CheckableItem text={option} value={option} />
  );

  /**
   * Prepares Item for saving.
   * @param {Object} props
   * @return {Promise}
   */
  prepareForSave = props =>
    new Promise((resolve, reject) => {
      if (!this.props.showTaxes || this.props.selected_currency !== 'INR') {
        delete props.tax;
        delete props.tax_group_id;
        delete props.tax_id;
        delete props.tax_inclusive;
        delete props.tax_rate;

        return resolve(props);
      }

      let {
        hsn_sac_code,
        hsn_sac_code_type,
        tax_rate,
        tax_inclusive,
        cess,
      } = props;

      // Detemine whether the item is new or not.
      const isNew = this.props.isNew || !this.props.item;

      // Set HSN/SAC
      if (hsn_sac_code_type) {
        if (hsn_sac_code_type === 'hsn') {
          props.hsn_code = hsn_sac_code;

          // Nullify or delete depending on whether is is a new item or it is being edited.
          deleteOrNullify(isNew, props, 'sac_code');
        } else if (hsn_sac_code_type === 'sac') {
          props.sac_code = hsn_sac_code;

          // Nullify or delete depending on whether is is a new item or it is being edited.
          deleteOrNullify(isNew, props, 'hsn_code');
        }
        delete props.hsn_sac_code_type;
        delete props.hsn_sac_code;
      }

      // Set tax rate
      if (tax_rate) {
        props.tax_rate = parseFloat(tax_rate) * 100;
      } else {
        // If it's a new item, delete the key from props. Otherwise nullify it.
        deleteOrNullify(isNew, props, 'tax_rate');
      }

      // Set tax inclusive/exclusive
      if (tax_inclusive) {
        props.tax_inclusive = tax_inclusive === '1';
      }

      // Set cess
      if (cess) {
        let cessPerc = cess;

        // Convert to an integer. (5% => 500)
        cessPerc = parseInt(parseFloat(cessPerc) * 100);

        // Find an existing cess.
        let { taxes } = this.state;

        // Promise to find a cess if one already exists, or create a new one if none exists.
        let findCessPromise = new Promise((_resolve, _reject) => {
          // Find existing cess.
          let existingCess = taxes.find(
            tax => isTaxOfTypeCess(tax) && tax.rate === cessPerc
          );

          // If there's one, resolve immediately.
          if (existingCess) {
            _resolve(existingCess);
            return;
          }

          // Create a new tax if one doesn't exist.
          this.props
            .saveTax({
              name: `Cess @ ${cessPerc / 100.0}%`,
              rate_type: 'percentage',
              rate: cessPerc,
            })
            .then(createdTax => _resolve(createdTax))
            .catch(err => _reject(err));
        });

        // Find a cess and assign it's tax ID.
        findCessPromise
          .then(newCess => {
            props.tax_id = newCess.id;
            delete props.cess;
            resolve(props);
          })
          .catch(err => reject(err));
      } else {
        // If cess is not present
        // Remove tax_id (Because cess is not present, we assume cess needs to be removed.)
        // Delete if new item, nullify otherwise.
        deleteOrNullify(isNew, props, 'tax_id');

        // Resolve
        resolve(props);
      }
    });

  save = _props => {
    // Prepare props for saving and then save.
    return this.prepareForSave({ ..._props })
      .then(props => {
        if (this.props.type) {
          props.type = this.props.type;
        }

        let saveItem = this.props.saveItem;

        if (this.props.isSubscriptionItem) {
          saveItem = this.props.saveSubscriptionItem;
        }

        return saveItem(props)
          .then(item => {
            this.props.showNotification({
              type: 'success',
              message: 'Item saved successfully',
            });
            this.props.onSave && this.props.onSave(item, this.props.item);
          })
          .catch(err => {
            this.setState({
              errors: err.errors,
            });
          });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  /**
   * Retuns the GST rates in a string format.
   * eg. ['0%', '5%', '12%', ...]
   * @return {Array}
   */
  getGSTRates = () => {
    const { gst } = this.state;

    // If GST doesn't exist yet, return an empty array.
    if (!(gst && gst.gst_tax_slabs_v2)) return [];

    return gst.gst_tax_slabs_v2.map(rate => {
      return `${rate / 10000}%`;
    });
  };

  /**
   * Sets the flag for tax radio display.
   */
  updateTaxRadioSection = (props = this.props) => {
    let { taxRate, cess } = props;

    let { showTaxRadios } = this.state;

    showTaxRadios = taxRate || cess;

    this.setState({
      showTaxRadios,
    });
  };

  /**
   * Makes Cess positive.
   * @param {Event} e
   */
  makeCessPositive = e => {
    let val = parseFloat(e.target.value);

    // Check for NaN
    if (isNaN(val)) {
      val = '';
    } else {
      val = Math.abs(val);
    }

    return setTimeout(() => this.props.change('cess', val), 0);
  };

  onCurrencyChange = option => {
    this.props.change('currency', option.name);
  };

  render() {
    const {
      handleSubmit,
      change,
      invalid,
      item,
      taxRate,
      taxInclusive,
      cess,
      selected_currency,
      user,
      showTaxes,
      currency,
    } = this.props;
    const { showCessForm, editingItem, showTaxRadios } = this.state;

    const gstRates = this.getGSTRates(),
      disableCurrencySelect =
        this.props.disableCurrencySelect ||
        !user.isInttCurrenciesEnabled ||
        (item && item.id),
      isNonINR = selected_currency !== 'INR';

    let aligenedStyleClass = `${showTaxes ? 'col-md-6' : 'col-md-12'}`;

    if (this.props.showTaxes && isNonINR) {
      aligenedStyleClass = 'col-md-6';
    }

    const cessForm = (
      <div class="row ItemCreationModal__cess-form">
        <div class="col-md-12">
          <label>Add Cess</label>
          <span
            class="text-primary ItemCreationModal__cess-form-cancel"
            onClick={() => {
              this.props.change('cess', null);
              this.setState({ showCessForm: false });
            }}
          >
            {cess ? 'Remove' : 'Cancel'}
          </span>
        </div>
        <div class="col-md-12">
          <div class="input-group">
            <span class="input-group-addon">%</span>
            <Field
              name="cess"
              component={InputField}
              type="number"
              placeholder="Cess"
              class="form-control input-number-no-arrows"
              min="0.00"
              onChange={this.makeCessPositive}
              autoFocus={true}
            />
          </div>
        </div>
      </div>
    );

    return (
      <div>
        <ModalHeader
          title={item && item.id ? 'Edit Item' : 'Add Item'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body ItemCreationModal">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="row">
              <div class={aligenedStyleClass}>
                <div class="form-group">
                  <label class="label-required">Name</label>
                  <div>
                    <Field
                      name="name"
                      placeholder="Item Name"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      validate={required()}
                    />
                  </div>
                </div>
                <div class="form-group">
                  <label class="label-required">Rate</label>
                  <div>
                    <div class="input-group input-group--amount">
                      <Input.CurrencySelect
                        name="currency"
                        onChange={this.onCurrencyChange}
                        parentQuerySelector=".ReactModal__Content"
                        defaultValue={currency}
                        disabled={disableCurrencySelect}
                      />
                      <Field
                        placeholder="Amount"
                        name="amountInINR"
                        component={InputField}
                        class="form-control input-number-no-arrows"
                        validate={required()}
                        type="number"
                      />
                      <span class="input-group-addon">per unit</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class={aligenedStyleClass}>
                <div class="form-group">
                  <label>Description</label>
                  <div>
                    <Field
                      name="description"
                      component="textarea"
                      class="form-control description"
                    />
                  </div>
                </div>
                {!showTaxes &&
                  item &&
                  item.id && (
                    <p>
                      Note: The updated item details will be reflected
                      everywhere in the future.
                    </p>
                  )}
                {(!showTaxes || isNonINR) && (
                  <div class="Modal__actions">
                    <AsyncButton
                      type="submit"
                      class="btn btn-primary btn-block"
                      text={this.props.saveLabel}
                      pendingText="Saving..."
                      disabled={invalid}
                      onClick={handleSubmit(this.save)}
                    />
                  </div>
                )}
              </div>
            </div>
            {showTaxes &&
              !isNonINR && (
                <div class="row ItemCreationModal__TaxContainer">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Tax Rate</label>
                      <PowerSelect
                        class="CheckableItem__PowerSelect ps-in-modal"
                        placeholder="Select Tax Rate"
                        options={gstRates}
                        selected={taxRate}
                        optionComponent={this.getTaxRateComponent}
                        onChange={({ option = null }) => {
                          this.props.change('tax_rate', option);
                        }}
                        searchEnabled={false}
                      />
                      <div class="row">
                        <div
                          class="col-md-12"
                          style={{ fontSize: '0.85em', marginTop: '4px' }}
                        >
                          Tax breakup will be calculated automatically.
                        </div>
                      </div>
                    </div>

                    {showCessForm ? (
                      cessForm
                    ) : (
                      <div class="row CreateItemModal__cess-form">
                        <div class="col-md-12">
                          <span
                            class="text-primary cursor-pointer"
                            onClick={e => this.setState({ showCessForm: true })}
                          >
                            + Add Cess
                          </span>
                        </div>
                      </div>
                    )}

                    {showTaxRadios ? (
                      <Fragment>
                        <div class="row">
                          <div class="col-md-6">
                            <Field
                              name="tax_inclusive"
                              component={RadioButton}
                              htmlValue="1"
                              label="Tax Inclusive"
                            />
                          </div>
                          <div class="col-md-6">
                            <Field
                              name="tax_inclusive"
                              component={RadioButton}
                              htmlValue="0"
                              label="Tax Exclusive"
                            />
                          </div>
                        </div>
                      </Fragment>
                    ) : null}
                  </div>
                  <div class="col-md-6">
                    <div class="row ItemCreationModal__hsn-radios">
                      <div class="form-group">
                        <div class="col-md-12">
                          <label style={{ marginBottom: '8px' }}>
                            HSN/SAC Code
                          </label>
                        </div>
                        <div class="col-md-6">
                          <Field
                            name="hsn_sac_code_type"
                            component={RadioButton}
                            htmlValue="hsn"
                            label="HSN Code"
                          />
                        </div>
                        <div class="col-md-6">
                          <Field
                            name="hsn_sac_code_type"
                            component={RadioButton}
                            htmlValue="sac"
                            label="SAC Code"
                          />
                        </div>
                      </div>
                      <div class="col-md-12">
                        <Field
                          placeholder="HSN/SAC Code"
                          name="hsn_sac_code"
                          component={InputField}
                          class="form-control"
                          validate={[sacLengthValidator]}
                        />
                      </div>
                    </div>
                    {item &&
                      item.id && (
                        <p style={{ marginTop: '8px' }}>
                          Note: The updated item details will be reflected
                          everywhere in the future.
                        </p>
                      )}

                    <div class="Modal__actions">
                      <AsyncButton
                        type="submit"
                        class="btn btn-primary btn-block"
                        text={this.props.saveLabel}
                        pendingText="Saving..."
                        disabled={invalid}
                        onClick={handleSubmit(this.save)}
                      />
                    </div>
                  </div>
                </div>
              )}
          </form>
        </div>
      </div>
    );
  }
}

AddItem.defaultProps = {
  onSave: () => {},
  saveLabel: 'Save',
};
