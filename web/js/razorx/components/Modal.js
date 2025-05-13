import React, { Component } from 'react';
import TransitionGroup from 'react-transition-group/TransitionGroup';
import CSSTransition from 'react-transition-group/CSSTransition';

import { observe, observable } from 'mobx';
import { observer } from 'mobx-react';
import { ModalMask, Modal } from 'common/new-ui/Modal';

const animObj = { enter: 300, exit: 300 };

class ModalStore {
  @observable.shallow modals = [];
  @observable.shallow toasts = [];
  @observable.shallow sliders = [];

  constructor() {
    window.addEventListener('focus', () => {
      this.tabActive = true;
      while (this.waitingToasts.length) {
        this.notify(this.waitingToasts.shift());
      }
    });
    window.addEventListener('blur', () => {
      this.tabActive = false;
    });
  }

  tabActive = true;
  waitingToasts = [];
  openModal = (modal) => this.modals.push(modal);
  closeModal = (_) => this.modals.pop();

  confirm = (message, confirmLabel = 'Yes', rejectLabel = 'Cancel') => {
    return new Promise((resolve, reject) => {
      var isResolved = false;
      let modals = this.modals;

      let Confirm = (
        <div className="confirm-modal">
          <header>Confirm</header>
          <div className="confirm-body">
            <div className="message">{message}</div>
            <div className="action-buttons">
              <button
                onClick={(_) => {
                  isResolved = 1;
                  this.closeModal();
                }}
                className="btn-confirm"
              >
                {confirmLabel}
              </button>
              <button onClick={(_) => this.closeModal()} className="btn-reject">
                {rejectLabel}
              </button>
            </div>
          </div>
        </div>
      );

      this.openModal(Confirm);
      let disposer = observe(modals, (_) => {
        if (modals.indexOf(Confirm) === -1) {
          isResolved && resolve();
          disposer();
        }
      });
    });
  };

  replaceSlider = (slider) => this.sliders.replace([slider]);
  openSlider = (slider) => this.sliders.push(slider);
  closeSlider = (_) => this.sliders.pop();

  notify = (toast) => {
    if (this.tabActive) {
      var len = this.toasts.push(toast);
      toast = this.toasts[len - 1];
      setTimeout((_) => {
        this.toasts.remove(toast);
      }, toast.duration || 5000);
    } else {
      this.waitingToasts.push(toast);
    }
  };

  notifyDone = (_) => this.notify({ message: 'Done!', duration: 3000, className: 'success' });
  notifySuccess = (message) => this.notify({ message, className: 'success' });
  notifyError = (message) => this.notify({ message, className: 'error' });
}

const store = new ModalStore();

observe(store.modals, (e) => {
  if (store.modals.length > 0) {
    document.body.classList.add('noscroll');
  } else {
    document.body.classList.remove('noscroll');
  }
});

@observer
export default class ModalContainer extends Component {
  componentDidMount() {
    document.addEventListener('keydown', this.escapePress, false);
  }

  componentWillUnMount() {
    document.removeEventListener('keydown', this.escapePress, false);
  }

  // Remove last modal on click of escape
  escapePress = (evt) => {
    evt = evt || window.event;
    if (evt.keyCode == 27) {
      store.closeModal();
    }
  };

  render() {
    let numToasts = store.toasts.length;
    return (
      <div id="fixed-container">
        <TransitionGroup>
          {store.sliders.length && (
            <CSSTransition classNames="slider" timeout={animObj}>
              <div id="slider-container">
                {store.sliders.map((slider, index) => (
                  <div className="slider" key={index}>
                    <div
                      className="slider-close"
                      onClick={(_) => {
                        store.sliders.remove(slider);
                      }}
                    >
                      &times;
                    </div>
                    {slider}
                  </div>
                ))}
              </div>
            </CSSTransition>
          )}
        </TransitionGroup>

        <TransitionGroup>
          {store.modals.length && (
            <CSSTransition classNames="modal" timeout={animObj}>
              <ModalMask maskClosable={false}>
                {store.modals.map((modal, index) => (
                  <Modal
                    key={index}
                    className={'admin'}
                    onClose={(_) => {
                      store.modals.remove(modal);
                    }}
                  >
                    {modal}
                  </Modal>
                ))}
              </ModalMask>
            </CSSTransition>
          )}
        </TransitionGroup>

        <TransitionGroup id="toast-container">
          {store.toasts.map(({ className, message }, index) => (
            <CSSTransition
              key={index}
              className={'toast ' + className}
              classNames="toast"
              timeout={animObj}
              style={{ maxWidth: 'inherit' }}
            >
              <div>{'' + message}</div>
            </CSSTransition>
          ))}
        </TransitionGroup>
      </div>
    );
  }
}

export const {
  openModal,
  confirm,
  closeModal,
  openSlider,
  replaceSlider,
  closeSlider,
  notify,
  notifyError,
  notifySuccess,
  notifyDone,
} = store;
