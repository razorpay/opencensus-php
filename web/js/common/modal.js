import React, { Component } from 'react';
import TransitionGroup from 'react-transition-group/TransitionGroup';
import CSSTransition from 'react-transition-group/CSSTransition';

import { observable } from 'mobx';
import { observer } from 'mobx-react';

class ModalStore {
  @observable modals = [];
  @observable toasts = [];
  @observable sliders = [];

  openModal = modal => this.modals.push(modal);
  closeModal = _ => this.modals.clear();

  replaceSlider = slider => this.sliders.replace([slider]);
  openSlider = slider => this.sliders.push(slider);
  closeSlider = _ => this.sliders.clear();

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

@observer
export default class ModalContainer extends Component {
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
              <div>{message}</div>
            </CSSTransition>
          ))}
        </TransitionGroup>
      </div>
    );
  }
}

const animObj = { enter: 300, exit: 300 };

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
    {modal.component}
  </div>
);

export const {
  openModal,
  closeModal,
  openSlider,
  replaceSlider,
  closeSlider,
  notify,
  notifyError,
  notifySuccess,
  notifyDone,
} = store;
