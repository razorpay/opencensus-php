import { Component, PropType } from 'react';
import { stringifyAddress } from 'rzp/utils/rzp-utils';
import { Check } from 'component/Input';
import PropTypes from 'prop-types';
import { Field } from 'redux-form';
import RadioButton from 'rzp/ui/Forms/RadioButton';

export default class Address extends Component {
  static propTypes = {
    /**
     * The address object.
     */
    address: PropTypes.object.isRequired,
  };

  render() {
    const { address } = this.props;

    return (
      <div class="row AddressSelectionModal__address">
        <div class="col-md-12">
          <Field
            name="address_id"
            component={RadioButton}
            htmlValue={address.id}
            label={() => (
              <p class="AddressSelectionModal__address-text">
                {stringifyAddress(address)}
              </p>
            )}
          />
        </div>
      </div>
    );
  }
}
