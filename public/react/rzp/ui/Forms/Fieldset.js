import React, { Component } from 'react'
import { Field } from 'redux-form'

const InputTypes = ['input', 'select', Field]

export default class Fieldset extends Component {
  renderChildren(children) {
    return React.Children.map(children, (child) => {
      if (!React.isValidElement(child)) {
        return child
      }
      let childProps = {}
      if (InputTypes.indexOf(child.type) !== -1) {
        childProps.readOnly = this.props.readOnly
      }

      if (child.props.children) {
        childProps.children = this.renderChildren(child.props.children)
      }

      return React.cloneElement(child, childProps)
    })
  }

  render() {
    let { readOnly, ...attrs } = this.props
    return (
      <fieldset {...attrs}>
        {this.renderChildren(this.props.children)}
      </fieldset>
    )
  }
}
