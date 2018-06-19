import { classList } from 'common/util';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

export default class EarlyAccessRPL extends React.Component {
  state = {
    isTextFieldShown: false,
  };

  onAction = () => {
    if (this.state.isTextFieldShown) {
      return;
    } else {
      this.setState({
        isTextFieldShown: true,
      });

      setTimeout(function() {
        const ele = document.getElementsByName('early-access-reason')[0];
        ele && ele.focus();
      }, 30);
    }
  };

  render() {
    return (
      <div class="Onboarding Onboarding--EarlyAccessRPL">
        <div class="illustration" />
        <div class="description">
          Event Tickets? Shop repairs? Falling hairs? How many problems in life
          you have? Just Chill madi and get Drunk with us!
        </div>
        <div
          class={classList(
            'form-fields',
            this.state.isTextFieldShown && 'drishy'
          )}
        >
          <Input.Textarea
            name="early-access-reason"
            placeholder="Describe your use-case for Payment Pages"
          />
          <Button.Primary
            type="button"
            onClick={this.onAction}
            style={{ padding: '11px 36px' }}
          >
            {this.state.isTextFieldShown ? 'SUBMIT' : 'GET EARLY ACCESS'}
          </Button.Primary>
        </div>
      </div>
    );
  }
}
