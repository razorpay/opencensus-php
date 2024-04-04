import PropTypes from 'prop-types';

const ModalHeader = (props) => (
  <div class="modal-header">
    {props.onCloseClick && (
      <button
        type="button"
        class="close"
        onClick={props.onCloseClick}
        data-testid="modal-header-close-btn"
      >
        <i class="i i-close" />
      </button>
    )}

    <h3 class={`modal-title ${props.extraClass}`}>{props.title}</h3>
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.oneOfType([PropTypes.string.isRequired, PropTypes.node.isRequired]),
  onCloseClick: PropTypes.func,
  extraClass: PropTypes.string,
};

export default ModalHeader;
