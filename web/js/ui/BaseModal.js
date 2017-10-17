import React, { Component } from 'react';

/*
  Description: Base modal with header
  Example: Open any pop from side bar in merchant enity details
  Props:
    title: Header of modal. Pass string or custom component or divs
    children: contents of the modal
    customClass: optional string props
*/
export default ({ header, children, customClass = '' }) => (
  <div class={`base-modal ${customClass}`}>
    <div class="modal-header">{header}</div>
    <div class="modal-body">{children}</div>
  </div>
);
