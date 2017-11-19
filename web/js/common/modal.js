import React, { Component } from 'react';
import ErrorBoundary from 'common/ErrorBoundary';
import TransitionGroup from 'react-transition-group/TransitionGroup';
import CSSTransition from 'react-transition-group/CSSTransition';

import { observe, observable } from 'mobx';
import { observer } from 'mobx-react';
import { animObj } from 'util/index';

class ModalStore {
  @observable.shallow modals = [];
  @observable.shallow toasts = [];
  @observable.shallow sliders = [];

  openModal = modal => this.modals.push(modal);
  closeModal = _ => this.modals.pop();

  confirm = (message, confirmLabel = 'Yes', rejectLabel = 'Cancel') => {
    return new Promise((resolve, reject) => {
      this.modals.push(
        <div class="confirm-modal">
          <header>Confirm</header>
          <div class="message">{message}</div>
          <div class="action-buttons">
            <button
              onClick={_ => this.closeModal() & resolve()}
              class="btn-confirm"
            >
              {confirmLabel}
            </button>
            <button
              onClick={_ => this.closeModal() & reject()}
              class="btn-reject"
            >
              {rejectLabel}
            </button>
          </div>
        </div>
      );
    });
  };

  replaceSlider = slider => this.sliders.replace([slider]);
  openSlider = slider => this.sliders.push(slider);
  closeSlider = _ => this.sliders.pop();

  notify = toast => {
    var len = this.toasts.push(toast);
    toast = this.toasts[len - 1];
    setTimeout(_ => {
      this.toasts.remove(toast);
    }, toast.duration || 5000);
  };

  notifyDone = _ =>
    this.notify({ message: 'Done!', duration: 1500, className: 'success' });
  notifySuccess = message => this.notify({ message, className: 'success' });
  notifyError = message => this.notify({ message, className: 'error' });
}

const store = new ModalStore();

observe(store.modals, e => {
  document.body.className = store.modals.length ? 'noscroll' : '';
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
  escapePress = evt => {
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
                  <div class="slider" key={index}>
                    <div
                      class="slider-close"
                      onClick={_ => {
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
              <div id="modal-container">
                {store.modals.map((s, index) => (
                  <Modal key={index} modal={s} />
                ))}
              </div>
            </CSSTransition>
          )}
        </TransitionGroup>

        <TransitionGroup id="toast-container">
          {store.toasts.map(({ className, message }, index) => (
            <CSSTransition
              key={index}
              class={'toast ' + className}
              classNames="toast"
              timeout={animObj}
            >
              <div>{'' + message}</div>
            </CSSTransition>
          ))}
        </TransitionGroup>
      </div>
    );
  }
}

const Modal = ({ modal }) => (
  <div class="modal">
    <div
      class="modal-close"
      onClick={_ => {
        store.modals.remove(modal);
      }}
    >
      &times;
    </div>
    <ErrorBoundary children={modal} />
  </div>
);

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
