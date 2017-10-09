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

  openSlider = slider => this.sliders.push(slider);
  closeSlider = _ => this.sliders.clear();
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
                {store.sliders.map((s, index) => (
                  <Slider key={index} slider={s} />
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
          {/* toasts are removed FIFO, hence the key */}
          {store.toasts.map((s, index) => (
            <Toast {...s} key={numToasts - index} />
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

const Slider = ({ slider }) => (
  <div class="slider">
    <div
      class="slider-close"
      onClick={_ => {
        store.sliders.remove(slider);
      }}
    >
      &times;
    </div>
    {slider.component}
  </div>
);

const Toast = ({ component, ...props }) => (
  <CSSTransition class="toast" classNames="toast" timeout={animObj} {...props}>
    {component}
  </CSSTransition>
);

export const { openModal, closeModal, openSlider, closeSlider } = store;
