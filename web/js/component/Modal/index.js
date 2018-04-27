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

    let classArray = rest.className
      ? rest.className.split(' ').map(cls => 'Modal-mask--' + cls)
      : '';

    return (
      <div
        class={classList(
          'Modal-mask',
          classArray,
          this.state.isHidden && 'Modal-mask--hide'
        )}
        onClick={maskClosable && this.onMaskClose}
      >
        <Modal {...rest}>{children}</Modal>
      </div>
    );
  }
}

// TODO: 1-a: Benefit of decoupling with ModalContent is, we can use loop over Modal with common close fn. and ModalContent just as children having its own custom properties
// TODO: 1-b: Alternatively, ModalContent can restrively child of Modal, and all modals must have property to tell Modal-container custom class and header/banner
// TODO: 1-c: Both approaches have trade-offs. Currently 'a' chosen to adapt merchant+admin with minimal changes on admin side.
/*
* Modal without modal mask. It takes care of close button functionality
* - ModalContainer uses this component. However, Modal can also be used independently.
* @props
*   - {Boolean, optional} showCloseBtn, by default close button is shown. Can be hidden if false is passed
*   - {Function} onClose, action on close btn press
* */
export const Modal = ({
  children,
  showCloseBtn = true,
  className,
  onClose,
}) => {
  let classArray = className
    ? className.split(' ').map(cls => 'Modal-container--' + cls)
    : '';

  return (
    <div class={classList('Modal-container', classArray)}>
      {showCloseBtn && (
        <span class="Modal-close" onClick={onClose}>
          &times;
        </span>
      )}

      {children}
    </div>
  );
};

/* ModalContent only provides the wrapper for the content
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
