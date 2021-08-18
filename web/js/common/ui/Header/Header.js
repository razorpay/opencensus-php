import { Component } from 'react';
import PropTypes from 'prop-types';
import { titleCase } from 'common/utils/rzp-utils';
import { connect } from 'react-redux';

@connect((state) => state.session || {}, null)
export default class Header extends Component {
  render() {
    let { title, showMode, isLoading, children, className } = this.props;
    return (
      <div class={`${className} header`}>
        {!!title && (
          <h1>
            {title} {showMode && `(${titleCase(this.props.mode)} Mode)`}
          </h1>
        )}
        {children}
      </div>
    );
  }
}

Header.defaultProps = {
  showMode: false,
};

Header.propTypes = {
  showMode: PropTypes.bool,
  title: PropTypes.string,
};
