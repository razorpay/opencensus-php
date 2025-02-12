import { Component, Fragment } from 'react';
import PropTypes from 'prop-types';

export default class AddressDisplay extends Component {
  static propTypes = {
    /**
     * Address object.
     */
    address: PropTypes.object.isRequired,
  };

  render() {
    let { line1, line2, zipcode, city, state, country } = this.props.address;

    return (
      <div className="address-display">
        <span className="address-display__line1">{line1}</span>
        {line2 && (
          <Fragment>
            , <span className="address-display__line2">{line2}</span>
          </Fragment>
        )}
        {city && (
          <Fragment>
            , <span className="address-display__city">{city}</span>
          </Fragment>
        )}
        , <span className="address-display__state">{state}</span>
        , <span className="address-display__country">{country}</span>
        {zipcode && (
          <Fragment>
            {' '}
            - <span className="address-display__zipcode">{zipcode}</span>
          </Fragment>
        )}
      </div>
    );
  }
}
