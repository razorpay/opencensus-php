import React, { Component } from 'react';
import TransitionGroup from 'react-transition-group/TransitionGroup';
import CSSTransition from 'react-transition-group/CSSTransition';

import { observable } from 'mobx';
import { observer } from 'mobx-react';

class ModalStore {
  @observable modals = [];
  @observable toasts = [];
  @observable sliders = [];

  openModal = modalObj => this.modals.push(modalObj);
  closeModal = _ => this.modals.clear();
}

const store = new ModalStore();

@observer
export default class ModalContainer extends Component {
  render() {
    let numToasts = store.toasts.length;
    return (
      <div id="fixed-container">
        <TransitionGroup id="slider-container">
          {store.sliders.length && <Backdrop class="slider-backdrop" />}
          {store.sliders.map((s, index) => <Slider {...s} key={index} />)}
        </TransitionGroup>

        <TransitionGroup id="modal-container">
          {store.modals.length === 2 && <Backdrop class="modal-backdrop" />}
          {store.modals.map((s, index) => <Modal {...s} key={index} />)}
        </TransitionGroup>

        <TransitionGroup id="toast-container">
          {store.toasts.length && <Backdrop class="toast-backdrop" />}
          {/* toasts are removed FIFO, hence the key */}
          {store.toasts.map((s, index) => (
            <Toast {...s} key={numToasts - index} />
          ))}
        </TransitionGroup>
      </div>
    );
  }
}

const animObj = { enter: 500, exit: 500 };

const Backdrop = ({ children, ...props }) => (
  <CSSTransition classNames="backdrop" timeout={animObj} {...props}>
    <div />
  </CSSTransition>
);

const Slider = ({ children, ...props }) => (
  <CSSTransition
    class="slider"
    classNames="slider"
    timeout={animObj}
    {...props}
  >
    {component}
  </CSSTransition>
);

const Modal = ({ component, ...props }) => (
  <CSSTransition class="modal" classNames="modal" timeout={animObj} {...props}>
    {component}
  </CSSTransition>
);

const Toast = ({ component, ...props }) => (
  <CSSTransition class="toast" classNames="toast" timeout={animObj} {...props}>
    {component}
  </CSSTransition>
);

export const { openModal, closeModal } = store;
