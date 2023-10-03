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
 * customClass: You can pass any customer class. Currently custom styles are put only for customClass = 'notes'
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
    const { allowEmptyTitle, children, placeholder, customClass = '' } = this.props;

    const definition = Array.isArray(children) ? [...children] : [children];

    if (!allowEmptyTitle) {
      while (definition.length > 0 && !definition[0]) {
        definition.shift();
      }
    }

    if (definition.length === 0) {
      return typeof placeholder !== 'undefined' ? <span>{placeholder}</span> : null;
    }

    const heading = definition[0];
    const body = definition.slice(1);

    return (
      <dl class={`rzp-definition ${customClass}`}>
        {heading && <dt>{heading}</dt>}
        {body.length > 0 ? body.map((item, key) => <dd key={key}>{item}</dd>) : <dd>&nbsp;</dd>}
      </dl>
    );
  }
}
