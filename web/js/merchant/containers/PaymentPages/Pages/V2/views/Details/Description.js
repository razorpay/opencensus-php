import { classList } from 'common/util';
import Input from 'component/Input';

const DESC_LIMIT = {
  DESKTOP: 720,
  MOBILE: 125,
};

const infoTxt = `Describe what the purpose of this page is and mention any additional details that might help the customer.

Note:
All URLs will convert to links.`;

export default class extends React.PureComponent {
  handleOnInput = ({ target }) => {
    this.autoAdjustHeight(target);
  };

  autoAdjustHeight(target) {
    if (!target) {
      return;
    }

    const content = target.value;
    const fakeEle = window.document.querySelector(
      '#description .fake-textarea'
    );

    fakeEle.innerHTML = content;
    this.elHeight = fakeEle.scrollHeight + 22 + 'px'; // 10 is combination of vertical padding and line height of the textarea in css
    target.style.height = this.elHeight;
  }

  componentDidMount() {
    this.autoAdjustHeight(
      document.body.querySelector('#description textarea[name="description"]')
    );
  }

  render() {
    const ele = document.body.querySelector(
      '#description textarea[name="description"]'
    );
    const hasVal = ele ? ele.value : this.props.description;

    return (
      <div id="description" class={classList(!hasVal && 'Input-highlight')}>
        <div class="fake-textarea" />
        <Input.Textarea
          name="description"
          placeholder="Enter page description"
          info={infoTxt}
          defaultValue={this.props.description}
          onInput={this.handleOnInput}
          onBlur={this.props.updateData}
        />
      </div>
    );
  }
}
