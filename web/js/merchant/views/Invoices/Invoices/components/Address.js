import { Component, PropType } from 'react';
import { stringifyAddress } from 'common/utils/rzp-utils';
import { Check } from 'common/new-ui/Input';
import PropTypes from 'prop-types';
import { Field } from 'redux-form';
import RadioButton from 'common/ui/Forms/RadioButton';

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
      <div className="row AddressSelectionModal__address">
        <div className="col-md-12">
          <Field
            name="address_id"
            component={RadioButton}
            htmlValue={address.id}
            label={() => (
              <p className="AddressSelectionModal__address-text">
                {stringifyAddress(address)}
              </p>
            )}
          />
        </div>
      </div>
    );
  }
}
