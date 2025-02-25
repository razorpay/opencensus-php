import React, { Component } from 'react';

export class RadioGroup extends Component {
  render() {
    const { input, meta, options } = this.props;
    const hasError = meta.touched && meta.error;

    return (
      <div className="radio-group-container">
        <div className={`radio-group ${hasError ? 'radio-error' : ''}`}>
          {options.map(o => (
            <label key={o.value}>
              <input
                type="radio"
                {...input}
                value={o.value}
                checked={o.value === input.value}
              />{' '}
              {o.title}
            </label>
          ))}
        </div>
        {hasError && <span className="error">{meta.error}</span>}
      </div>
    );
  }
}
