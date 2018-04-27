import { classList } from 'common/util';
import ErrorBoundary from 'common/ErrorBoundary';

/* Modal with backdrop mask, closes with animation
* @props
*   - {Function} onClose,
*   - {Boolean, optional} maskClosable, Whether to close modal on clicking outside the modal
* */
export default class ModalContainer extends React.PureComponent {
  state = {};

  onMaskClose = e => {
    const modalContent = document.getElementsByClassName('Modal-container')[0];

    // Don't close modal if clicked inside modal-content (but not on cross btn)
    if (
      modalContent.contains(e.target) &&
      !e.target.classList.contains('Modal-close') > -1
    ) {
      return;
    }

    this.onClose(e);
  };

  // Fadeout based closing modal
  onClose = e => {
    this.setState({
      isHidden: true,
    });

    setTimeout(_ => this.props.onClose(e), 400);
  };

  render() {
    const { children, maskClosable = false, ...rest } = this.props;

    return (
      <div
        class={classList(
          'Modal-mask',
          rest.className && 'Modal-mask--' + rest.className,
          this.state.isHidden && 'Modal-mask--hide'
        )}
        onClick={maskClosable && this.onMaskClose}
      >
        <Modal {...rest}>{children}</Modal>
      </div>
    );
  }
}

/*
* Modal without modal mask. It takes care of close button functionality
* ModalContainer uses this component. However, Modal can also be used independently.
* @props
*   - {Boolean, optional} showCloseBtn, by default close button is shown. Can be hidden if false is passed
*   - {Function} onClose, action on close btn press
* */
export const Modal = ({
  children,
  showCloseBtn = true,
  className,
  onClose,
}) => (
  <div
    class={classList(
      'Modal-container',
      className && 'Modal-container--' + className
    )}
  >
    {showCloseBtn && (
      <span class="Modal-close" onClick={onClose}>
        &times;
      </span>
    )}

    {children}
  </div>
);

/* ModalContent only provides the container for the content
* @props
*   - {String/React Node, optional} header, Add custom Class to the modal content
*   - {String/React Node, optional} banner, pass function (Example: check 'invite a merchant')
*   - {Boolean, optional} noPadding, By default modal body has padding if it has header over it. Set noPadding true to remove padding.
* */
export const ModalContent = ({
  header,
  banner,
  children,
  className = '',
  noPadding = false,
}) => (
  <ErrorBoundary>
    <div class={classList('Modal-content', className)}>
      {header && <header>{header}</header>}
      {banner}
      <div class={classList('Modal-body', noPadding && 'no-padding')}>
        {children}
      </div>
    </div>
  </ErrorBoundary>
);
