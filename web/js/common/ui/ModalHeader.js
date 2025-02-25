import PropTypes from 'prop-types';
import React from 'react';

const ModalHeader = (props) => (
  <div className="modal-header">
    {props.onCloseClick && (
      <button
        type="button"
        className="close"
        onClick={props.onCloseClick}
        data-testid="modal-header-close-btn"
      >
        <i className="i i-close" />
      </button>
    )}

    <h3 className={`modal-title ${props.extraClass}`}>{props.title}</h3>
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.oneOfType([PropTypes.string.isRequired, PropTypes.node.isRequired]),
  onCloseClick: PropTypes.func,
  extraClass: PropTypes.string,
};

export default ModalHeader;
