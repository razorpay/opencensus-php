import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import * as ModalActions from 'rzp/modules/modals';
import Address from '../Address';
import PropTypes from 'prop-types';
import { reduxForm, formValueSelector } from 'redux-form';

const selector = formValueSelector('addressSelectionList');

@connect(
  state => ({
    address_id: selector(state, 'address_id'),
  }),
  {
    ...ModalActions,
  }
)
@reduxForm({
  form: 'addressSelectionList',
})
export default class List extends Component {
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
  save = props => {
    const { address_id } = props;

    const { addresses } = this.props;

    const address = addresses.find(addr => addr.id === address_id);

    this.props.onSave(address);
    return address;
  };

  render() {
    const {
      closeModal,
      onAddClick,
      addresses,
      header,
      handleSubmit,
      address_id,
    } = this.props;

    const { selected } = this.state;

    const showAddButton = !(addresses && addresses.length >= 3);

    return (
      <div>
        <ModalHeader title={header} onCloseClick={closeModal} />

        <div class="modal-body AddressSelectionModal AddressSelectionModal__full">
          <Alert type="error" message={this.state.errors} />
          <div class="row AddressSelectionModal__shrunk">
            <div class="col-md-12">
              <label>All Addresses</label>
            </div>
          </div>
          <form onSubmit={handleSubmit(this.save)}>
            <div class="AddressSelectionModal__list">
              {addresses.map((address, i) => (
                <Address
                  address={address}
                  key={`address_list_address_${address.id}_${i}`}
                />
              ))}
            </div>

            <div class="AddressSelectionModal__shrunk AddressSelectionModal__bottom">
              {showAddButton ? (
                <div
                  class="text-primary cursor-pointer AddressSelectionModal__other-cta"
                  onClick={onAddClick}
                >
                  + Add new Address
                </div>
              ) : (
                <p>
                  No new addresses can be added for this customer at this
                  moment.
                </p>
              )}

              <div class="row">
                <div class="col-md-12">
                  <div class="Modal__actions">
                    <AsyncButton
                      type="submit"
                      class="btn btn-primary btn-block"
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
