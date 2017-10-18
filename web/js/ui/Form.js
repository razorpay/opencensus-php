import React, { Component } from 'react';
import { adminFetch as fetch } from 'util/fetch';
import { notifyError } from 'common/modal';

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
      <form onSubmit={::this.onSubmit} {...props} class={className}>
        {children}
      </form>
    );
  }

  onSubmit(e) {
    e.preventDefault();

    if (!this.state.pending) {
      this.setState({
        pending: true,
      });

      this.props
        .onSubmit(serialize(e.target))
        .catch(e => notifyError(e.message))
        .then(() => {
          this.setState({
            pending: false,
          });
        });
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
    function(data, el) {
      var { name, value } = el;
      if (el.type === 'checkbox') {
        value = el.checked ? el.value || 1 : 0;
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
    {}
  );
}
