import { PropTypes } from 'react';

const ModalHeader = props => (
  <div class="modal-header">
    {props.onCloseClick &&
      <button type="button" class="close" onClick={props.onCloseClick}>
        <i class="icon icon-close" />
      </button>}

    <h3 class="modal-title">{props.title}</h3>
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.string.isRequired,
  onCloseClick: PropTypes.func,
};

export default ModalHeader;
