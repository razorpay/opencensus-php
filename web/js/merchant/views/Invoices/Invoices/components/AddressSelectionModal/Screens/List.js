import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import * as ModalActions from 'merchant_common/reducers/modals';
import Address from 'merchant/views/Invoices/Invoices/components/Address';
import PropTypes from 'prop-types';
import { reduxForm, formValueSelector } from 'redux-form';
import { compose } from 'redux';

const selector = formValueSelector('addressSelectionList');

class List extends Component {
  static propTypes = {
    /**
     * Array of Addresses (objects)
     */
    addresses: PropTypes.array,

    /**
     * Callback for when "Select Address" is clicked.
     */
    onSave: PropTypes.func,

    /**
     * Callback for when "+ Add an Address" is clicked.
     */
    onAddClick: PropTypes.func,

    /**
     * Modal title.
     */
    header: PropTypes.string,

    /**
     * Selected Address
     */
    selected: PropTypes.object,
  };

  static defaultProps = {
    addresses: [],
    onSave: () => {},
    onAddClick: () => {},
    header: 'Select Address',
  };

  constructor(props) {
    super(props);
    this.state = {
      errors: null,
    };
  }

  componentDidMount() {
    const { selected, change } = this.props;

    if (selected && selected.id) {
      change('address_id', selected.id);
    }
  }

  /**
   * Invokes the onSave callback.
   */
  save = (props) => {
    const { address_id } = props;

    const { addresses } = this.props;

    const address = addresses.find((addr) => addr.id === address_id);

    this.props.onSave(address);
    return address;
  };

  render() {
    const { closeModal, onAddClick, addresses, header, handleSubmit, address_id } = this.props;

    const { selected } = this.state;

    const showAddButton = !(addresses && addresses.length >= 3);

    return (
      <div>
        <ModalHeader title={header} onCloseClick={closeModal} />

        <div className="modal-body AddressSelectionModal AddressSelectionModal__full">
          <Alert type="error" message={this.state.errors} />
          <div className="row AddressSelectionModal__shrunk">
            <div className="col-md-12">
              <label>All Addresses</label>
            </div>
          </div>
          <form onSubmit={handleSubmit(this.save)}>
            <div className="AddressSelectionModal__list">
              {addresses.map((address, i) => (
                <Address address={address} key={`address_list_address_${address.id}_${i}`} />
              ))}
            </div>

            <div className="AddressSelectionModal__shrunk AddressSelectionModal__bottom">
              {showAddButton ? (
                <div
                  className="text-primary cursor-pointer AddressSelectionModal__other-cta"
                  onClick={onAddClick}
                >
                  + Add new Address
                </div>
              ) : (
                <p>No new addresses can be added for this customer at this moment.</p>
              )}

              <div className="row">
                <div className="col-md-12">
                  <div className="Modal__actions">
                    <AsyncButton
                      type="submit"
                      className="btn btn-primary btn-block"
                      text="Select Address"
                      pendingText="Saving..."
                      disabled={!address_id}
                      onClick={handleSubmit(this.save)}
                    />
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      address_id: selector(state, 'address_id'),
    }),
    {
      ...ModalActions,
    },
  ),
  reduxForm({
    form: 'addressSelectionList',
  }),
)(List);
