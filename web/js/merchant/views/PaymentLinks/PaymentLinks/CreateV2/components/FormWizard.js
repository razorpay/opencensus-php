import React from 'react';
import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import { isMobileDevice } from 'merchant/components/Home/data';
import Button, { AsyncBtn } from 'common/new-ui/Button';

export const FORM_CLASS_NAME = 'PaymentLinks--Create-Form';

export default class FormWizard extends React.Component {
  state = { disableSubmit: false };

  componentDidMount() {
    this.toggleDisableState();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  toggleDisableState = () => {
    // if value not selected, html marks it as ':invalid' which is tehnically valid in our case. Hence, relying on is-invalid.
    const invalidFields = document.querySelectorAll(`.${FORM_CLASS_NAME} .Input.is-invalid`);
    const disableSubmit = invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  };

  redirectToListView = () => this.props.history.push('/paymentlinks');

  render() {
    const { props, state } = this;
    const disableSubmit = props.isLoading || state.disableSubmit;
    const title = !props.isLoading && props.title;
    return (
      <div class="PaymentLinks--CreateV2-wizard">
        <div class={props.isModalView ? 'title' : 'Paymentlink-layout-title'}>
          {title}{' '}
          {isMobileDevice() && (
            <span onClick={this.redirectToListView}>
              <i class="i i-close" />
            </span>
          )}
        </div>
        <div class="form-container">
          <Form class={FORM_CLASS_NAME} onChange={props.onChange}>
            <main>
              {props.isLoading ? (
                <div className="page-center">
                  <Spinner />
                </div>
              ) : (
                props.children
              )}
            </main>

            <footer>
              {props.isModalView && (
                <Button class="btn-outline" type="button" onClick={props.onClose}>
                  Cancel
                </Button>
              )}

              {isMobileDevice() && !props.isModalView && (
                <Button type="button" onClick={this.redirectToListView}>
                  Cancel
                </Button>
              )}

              <AsyncBtn.Primary
                type="submit"
                pendingState="Creating..."
                onClick={props.onSubmit}
                disabled={disableSubmit}
              >
                Create Payment Link
              </AsyncBtn.Primary>
            </footer>
          </Form>
        </div>
      </div>
    );
  }
}
