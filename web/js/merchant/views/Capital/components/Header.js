import React from 'react';
import PropTypes from 'prop-types';

const Header = ({ heading, subHeading }) => {
  const isHeadingTypeString = typeof heading === 'string';

  return (
    <header className="application-stage-header">
      {isHeadingTypeString ? <h2>{heading}</h2> : heading}
      {subHeading && <h4>{subHeading}</h4>}
    </header>
  );
};

Header.propTypes = {
  heading: PropTypes.oneOfType([PropTypes.string, PropTypes.element]).isRequired,
  subHeading: PropTypes.oneOfType([PropTypes.string, PropTypes.element]),
};

Header.defaultProps = {
  subHeading: '',
};

export default Header;
