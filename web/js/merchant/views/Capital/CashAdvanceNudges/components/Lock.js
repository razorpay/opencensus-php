import React from 'react';
import PropTypes from 'prop-types';

const Lock = ({ color }) => {
  return (
    <svg className="lock-icon" width="11" height="12" xmlns="http://www.w3.org/2000/svg">
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M2.182 4.91V3.272a3.273 3.273 0 1 1 6.545 0v1.636h.546c.904 0 1.636.733 1.636 1.636v3.819c0 .903-.732 1.636-1.636 1.636H1.636A1.636 1.636 0 0 1 0 10.364V6.545C0 5.642.733 4.91 1.636 4.91h.546Zm1.09-1.637a2.182 2.182 0 1 1 4.364 0v1.636H3.273V3.273ZM1.637 6a.545.545 0 0 0-.545.545v3.819c0 .3.244.545.545.545h7.637a.545.545 0 0 0 .545-.545V6.545A.545.545 0 0 0 9.273 6H1.636Z"
        fill={color}
      />
    </svg>
  );
};

export default Lock;

Lock.propTypes = {
  color: PropTypes.string,
};

Lock.defaultProps = {
  color: 'currentColor',
};
