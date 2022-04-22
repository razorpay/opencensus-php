import React from 'react';

import Input from 'common/new-ui/Input';

import { classList } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';
import track from '../track/';

const callTrackers = () => {
  return (event) => {
    const value = event?.target?.value;
    const errorElement = document.querySelector('#title .Input-error');

    if (errorElement) {
      track.wysiwyg.titleEnterError(value, errorElement.innerText);
    } else {
      track.wysiwyg.titleEnterSuccess(value);
    }
  };
};
export default class extends React.Component {
  autoAdjustHeight(target) {
    if (!target) {
      return;
    }

    const content = target.value;
    const fakeEle = window.document.querySelector('#title .fake-textarea');

    fakeEle.value = content;
    const newHeight = fakeEle.scrollHeight;

    target.style.height = `${newHeight}px`;
  }

  componentDidMount() {
    this.autoAdjustHeight(document.body.querySelector('#title textarea[name="title"]'));
  }

  onChange = (value) => {
    const hasError = !!document.querySelectorAll('#title .is-invalid').length;

    this.props.updateData({
      target: {
        name: 'title',
        value: hasError ? '' : value,
      },
    });
  };

  debounce_onChange = debounce(this.onChange, 100);

  handleOnInput = ({ target }) => {
    this.autoAdjustHeight(target);

    this.debounce_onChange(target.value);
  };

  render() {
    const ele = document.body.querySelector('#title textarea[name="title"]');
    const hasVal = ele ? ele.value : this.props.title;

    return (
      <div id="title" class={classList('title title--big', !hasVal && 'Input-highlight')}>
        <textarea class="fake-textarea" readOnly />
        <Input.Textarea
          name="title"
          placeholder="Enter page title here"
          info="Heading of your page"
          defaultValue={this.props.title}
          onInput={this.handleOnInput}
          required
          // eslint-disable-next-line consistent-return
          validator={(val) => {
            if (!val.trim()) {
              return 'Page title cannot be empty';
            }
            if (val && val.length > 40) {
              return 'Title cannot be more than 40 characters';
            }
          }}
          onKeyPress={(e) => {
            if (e.which === 13) {
              e.preventDefault();
            }
          }}
          onBlur={callTrackers()}
        />
      </div>
    );
  }
}
