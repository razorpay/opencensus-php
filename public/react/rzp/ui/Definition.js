import React, { Component } from 'react';

/*
 * Description:
 * Displays children in form of Title and Content, The first child
 * will be title and rest of the children will be the body, Title will
 * be bigger in font size than rest of the content, by default it will
 * also make sure that the heading is not empty. If given a
 * placeholder prop, the component will display it if there are no
 * resultant children
 *
 * Usage:
 * <Definiton>
 *   <span>Contact Details</span>
 *   <span>Anthony Gonsalves</span>
 *   <span>anthony@gonsalves.com</span>
 *   <span>9988998899</span>
 * </Definiton>
 */
export default class Definition extends Component {
  render() {
    let children = Array.isArray(this.props.children)
      ? [...this.props.children]
      : [this.props.children];

    const allowEmptyTitle = this.props.allowEmptyTitle,
      placeholder = this.props.placeholder;

    if (!allowEmptyTitle) {
      while (children.length > 0 && !children[0]) {
        children.shift();
      }
    }

    if (children.length === 0) {
      return typeof placeholder !== 'undefined'
        ? <span>
            {placeholder}
          </span>
        : null;
    }

    const heading = children[0],
      body = children.slice(1);

    return (
      <dl class="rzp-definition">
        {heading &&
          <dt>
            {heading}
          </dt>}
        {body.map((item, key) =>
          <dd key={key}>
            {item}
          </dd>
        )}
      </dl>
    );
  }
}
