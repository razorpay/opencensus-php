/* eslint-disable */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { emptySliderStack } from 'merchant_common/reducers/multiSlider';
import { MultiSlider__Component } from './MultiSliderStyle';
import { classList } from 'common/utils/rzp-utils';
import PropTypes from 'prop-types';
import { compose } from 'redux';

class MultiSliderComponent extends Component {
  componentWillUnmount = () => {
    const { onClose } = this.props;
    if (onClose && typeof onClose === 'function') onClose();
  };

  render() {
    const { children, emptySliderStack, width, transitionDuration, position, classString } =
      this.props;

    return (
      <MultiSlider__Component
        width={width}
        transitionDuration={transitionDuration}
        position={position}
        classString={classList('MultiSlider', classString)}
      >
        <div className="MultiSlider__Content">
          <button type="button" className="close close-primary" onClick={emptySliderStack}>
            <i className="i i-close" />
          </button>
          {children}
        </div>
      </MultiSlider__Component>
    );
  }
}

MultiSliderComponent.defaultProps = {
  width: 480,
  transitionDuration: 300,
  position: 'right',
  onClose: null,
  classString: null,
};

MultiSliderComponent.propTypes = {
  width: PropTypes.number.isRequired,
  transitionDuration: PropTypes.number.isRequired,
  position: PropTypes.string.isRequired,
  onClose: PropTypes.func,
  classString: PropTypes.string,
};

export default compose(
  connect((state) => state.multiSlider, { emptySliderStack }),
  withRouter,
)(MultiSliderComponent);
