import React, { Component } from 'react';
import { adminFetch as fetch } from 'util/fetch';

export default class Form extends Component {
  state = {
    pending: false,
  };

  render() {
    let { children, onSubmit, onSuccess, ...props } = this.props;

    return (
      <form onSubmit={::this.onSubmit} {...props}>
        {children}
      </form>
    );
  }

  onSubmit(e) {
    e.preventDefault();
    var data = this.serialize(e.target);

    if (this.props.onSubmit) {
      return this.props.onSubmit(data);
    }

    if (!e.target.action) {
      return false;
    }

    this.setState({
      pending: true,
    });

    fetch({
      route: e.target.getAttribute('action'),
      data,
    })
      .then(this.props.onSuccess)
      .catch(({ response }) => {
        console.error(response.data);
      })
      .then(() => {
        this.setState({
          pending: false,
        });
      });
  }

  serialize(form) {
    return Array.prototype.reduce.call(
      form.querySelectorAll('[name]'),
      function(data, el) {
        var { name, value } = el;
        if (el.type === 'checkbox') {
          value = el.checked ? 1 : 0;
        }
        if (value) {
          // item[foo] → item.foo
          var nameSplit = name.match(/(.+)\[(\w+)\]$/);
          if (nameSplit) {
            if (!data[nameSplit[1]]) {
              data[nameSplit[1]] = {};
            }
            data[nameSplit[1]][nameSplit[2]] = value;
          } else {
            data[name] = value;
          }
        }
        return data;
      },
      {}
    );
  }
}
