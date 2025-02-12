import React, { Component } from 'react';
import { adminGet as fetch } from 'razorx/helpers/admin-fetch';
import { notifyError } from 'razorx/components/Modal';

export default class Form extends Component {
  state = {
    pending: false,
  };

  render() {
    let { children, onSubmit, className, ...props } = this.props;

    if (this.state.pending) {
      className = ['pending'].concat(className || '').join(' ');
    }

    return (
      <form onSubmit={this.onSubmit.bind(this)} {...props} className={className}>
        {children}
      </form>
    );
  }

  onSubmit(e) {
    e.preventDefault();

    if (!this.state.pending) {
      let returnValue = this.props.onSubmit(serialize(e.target));
      if (returnValue instanceof Promise) {
        this.setState({
          pending: true,
        });

        returnValue
          .catch((e) => notifyError(e.message))
          .then(() => {
            this.setState({
              pending: false,
            });
          });
      }
    }
  }
}

export function postForm(form) {
  var el = document.createElement('input');
  el.name = '_token';
  el.value = document.querySelector('[name=csrf-token]').content;
  form.appendChild(el);
  form.action = '/api/' + form.getAttribute('action');
  form.enctype = 'multipart/form-data';
  form.target = '_blank';
  form.submit();
}

export function serialize(form) {
  return Array.prototype.reduce.call(
    form.querySelectorAll('[name]'),
    function (data, el) {
      let { name, value } = el;
      if (el.type === 'checkbox') {
        value = el.checked ? '1' : '0';
      }
      if (el.type === 'radio') {
        if (!el.checked) {
          return data;
        }
        value = el.value;
      }
      if (el.type === 'select-multiple') {
        value = [];
        const selectedOptions = Array.from(el.selectedOptions);

        selectedOptions.forEach((option) => value.push(option.value));
      }
      if (el.type === 'file') {
        value = el.files.length ? el.files : null;
      }

      if (value) {
        // item[foo] → item.foo
        var nameSplit = name.match(/(.+)\[(\w+)\]$/);
        if (nameSplit) {
          let arrayIndex = nameSplit[2];
          let array = data[nameSplit[1]];
          if (arrayIndex === '0') {
            data[nameSplit[1]] = [value];
          } else if (/^\d+$/.test(arrayIndex)) {
            array.push(value);
          } else {
            if (!array) {
              data[nameSplit[1]] = {};
            }
            data[nameSplit[1]][arrayIndex] = value;
          }
        } else {
          data[name] = value;
        }
      }
      return data;
    },
    {},
  );
}
