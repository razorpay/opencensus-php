import React, { Component } from 'react';
import { Field } from 'redux-form';

/*
    The native `fieldset` tag doesn't support `readonly` attrbute. Setting `disabled` on fieldset will disable the buttons inside the form too.
    This might restrict us from having actions like `Verify PAN` alongside the form field.

    This component wrapper loops through its children & sets the `readonly` on its form elements
 */

const InputTypes = ['input', 'textarea', Field];

export default class Fieldset extends Component {
  renderChildren(children) {
    return React.Children.map(children, child => {
      if (!React.isValidElement(child)) {
        return child;
      }
      let childProps = {};
      if (InputTypes.indexOf(child.type) !== -1) {
        childProps.readOnly = this.props.readOnly;
      }

      if (child.props.children) {
        childProps.children = this.renderChildren(child.props.children);
      }

      return React.cloneElement(child, childProps);
    });
  }

  render() {
    let { readOnly, ...attrs } = this.props;
    return (
      <fieldset {...attrs}>
        {this.renderChildren(this.props.children)}
      </fieldset>
    );
  }
}
