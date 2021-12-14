import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

/* Modal with backdrop mask, closes with animation
 * @props
 *   - {Function} onClose,
 *   - {Boolean, optional} maskClosable, Whether to close modal on clicking outside the modal
 *   - {Boolean, optional} isBlur, To blur background
 * */
export class ModalMask extends React.PureComponent {
  state = {};

  componentWillMount() {
    if (this.props.isBlur) this.toggleBlur(true);
  }

  componentWillUnmount() {
    if (this.props.isBlur) this.toggleBlur(false);
    document.body.classList.remove('noscroll');
  }

  toggleBlur(toAdd) {
    const body = document.body;
    if (toAdd) {
      if (!body.classList.contains('blur')) {
        body.classList.add('blur');
      }
    } else {
      body.classList.remove('blur');
      body.classList.remove('noscroll');
    }
  }

  onMaskClose = (e) => {
    const modalContent = document.getElementsByClassName('Modal-container')[0];

    // Don't close modal if clicked inside modal-content (but not on cross btn)
    if (modalContent.contains(e.target) && !e.target.classList.contains('Modal-close') > -1) {
      return;
    }

    this.onClose(e);
  };

  // Fadeout based closing modal
  onClose = (e) => {
    this.setState({
      isHidden: true,
    });

    setTimeout((_) => this.props.onClose(e), 400);
  };

  render() {
    const { children, maskClosable = false, allowScroll, ...rest } = this.props;

    const classArray = rest.className
      ? rest.className.split(' ').map((cls) => `Modal-mask--${cls}`)
      : '';

    return (
      <div
        className={classList('Modal-mask', classArray, this.state.isHidden && 'Modal-mask--hide')}
        onClick={maskClosable ? this.onMaskClose : undefined}
      >
        {children}
      </div>
    );
  }
}

// TODO: 1-a: Benefit of decoupling with ModalContent is, we can use loop over Modal with common close fn. and ModalContent just as children having its own custom properties
// TODO: 1-b: Alternatively, ModalContent can restrively child of Modal, and all modals must have property to tell Modal-container custom class and header/banner
// TODO: 1-c: Both approaches have trade-offs. Currently 'a' chosen to adapt merchant+admin with minimal changes on admin side.
/*
 * Modal without modal mask. It takes care of close button functionality
 * - ModalMask uses this component. However, Modal can also be used independently.
 * @props
 *   - {Boolean, optional} showCloseBtn, by default close button is shown. Can be hidden if false is passed
 *   - {Function} onClose, action on close btn press
 *   - {Function, optional} onCloseCB, Callback after closing modal
 * */
export class Modal extends React.PureComponent {
  componentDidMount() {
    // Add the class if not present on body
    if (!this.props.allowScroll) {
      document.body.classList.add('noscroll');
    }
  }

  componentWillUnmount() {
    document.body.classList.remove('noscroll');
  }

  render() {
    const {
      children,
      showCloseBtn = true,
      className,
      onClose,
      onCloseCB,
      allowScroll,
      fadedCloseButton = false,
      canDisableCloseBtn = false,
      fullWidth = false,
      ...rest
    } = this.props;

    const classArray = className
      ? className.split(' ').map((cls) => `Modal-container--${cls}`)
      : '';
    const mobileFullWidthModalClass = fullWidth ? 'mobile-full-width-modal' : '';

    return (
      <div
        className={classList('Modal-container', classArray, mobileFullWidthModalClass)}
        {...rest}
      >
        {showCloseBtn && (
          <span
            className={`Modal-close ${fadedCloseButton ? 'Modal-close-faded' : ''} ${
              canDisableCloseBtn ? 'Modal-close-disable' : ''
            }`}
            onClick={(e) => {
              if (onCloseCB) onCloseCB(e);
              document.body.classList.remove('noscroll');

              onClose(e);
            }}
          >
            &times;
          </span>
        )}

        <ErrorBoundary resetOnProps>{children}</ErrorBoundary>
      </div>
    );
  }
}

/* ModalContent only provides the wrapper for the content
 * @props
 *   - {String/React Node, optional} header, Add custom Class to the modal content
 *   - {String/React Node, optional} banner, pass function (Example: check 'invite a merchant')
 *   - {Boolean, optional} noPadding, By default modal body has padding if it has header over it. Set noPadding true to remove padding.
 * */
export const ModalContent = ({ header, banner, children, className = '', noPadding = false }) => (
  <div className={classList('Modal-content', className)}>
    {header && <header>{header}</header>}
    {banner}
    <div className={classList('Modal-body', noPadding && 'no-padding')}>{children}</div>
  </div>
);
