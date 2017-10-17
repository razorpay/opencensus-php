import React, { Component } from 'react';

/*
  Description: Base modal with header
  Example: Open any pop from side bar in merchant enity details
  Props:
    title: Header of modal. Pass string or custom component or divs
    children: contents of the modal
*/
export default ({ title, children, size = 'small' }) => [
  <div class={`base-modal modal-${size}`}>
    <div class="modal-header">{title}</div>
    <div class="modal-body">{children}</div>
  </div>,
];
