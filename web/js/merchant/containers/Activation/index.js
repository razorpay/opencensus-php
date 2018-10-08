import { Component } from 'react';
import { connect } from 'react-redux';

import { Modal, ModalContent } from 'component/Modal';
import { classList } from 'common/util';
import Spinner from 'rzp/ui/Spinner';
import { merchantFetch } from 'merchant/utils/ajax';

import KycForm from './new';
import InstantActivation from './Instant';

@connect(state => ({ user: state.session.user }))
export default class ActivationContainer extends Component {
  constructor(props) {
    super(props);

    this.state = {
      data: null,
      categories: null,
      additionalModalClass: null,
    };

    this.fetchActivationDetails = this.fetchActivationDetails.bind(this);
    this.setAdditionalModalClass = this.setAdditionalModalClass.bind(this);
  }

  setAdditionalModalClass(additionalModalClass) {
    return (
      additionalModalClass !== this.state.additionalModalClass &&
      this.setState({
        additionalModalClass,
      })
    );
  }

  fetchActivationDetails(accountId) {
    return Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        // For accountId, mode must be respected, otherwise accountId in Headers would be ignored in api.
        mode: !!accountId ? this.props.session.mode : 'live',
        accountId,
      }),
      !accountId && merchantFetch('merchant/activation/business_categories'),
    ]).then(([data, categories]) => {
      data = data.data;
      categories = categories.data;

      this.setState({
        data,
        categories,
      });

      return [data, categories];
    });
  }

  componentWillMount() {
    this.fetchActivationDetails(this.props.accountId);
  }

  setOnCloseCb(cb) {
    this.onCloseCB = cb;
  }

  render() {
    const { data, categories, additionalModalClass } = this.state,
      { showInstantActivation, activationFlow } = this.props.user,
      commonProps = {
        accountId: this.props.accountId,
        fetchActivationDetails: this.fetchActivationDetails,
        data,
        categories,
      },
      isLoading = !data,
      // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
      isModal = !!this.props.onClose;

    let content = null,
      modalClasses = ['animate-down'];

    if (isLoading) {
      modalClasses = ['spinner', 'transparent'];

      content = (
        <div class="spinner-container">
          <div
            class={classList(
              'spin-btn large page-center visible',
              isModal && 'gray'
            )}
          />
        </div>
      );
    } else {
      if (
        showInstantActivation &&
        (!activationFlow || activationFlow === 'blacklist')
      ) {
        content = <InstantActivation {...commonProps} />;
      } else {
        content = (
          <KycForm
            {...commonProps}
            setAdditionalModalClass={this.setAdditionalModalClass}
          />
        );
      }
    }

    if (additionalModalClass) {
      modalClasses.push(additionalModalClass);
    }

    return isModal ? (
      <Modal
        class={classList(...modalClasses)}
        onClose={this.props.onClose}
        onCloseCB={this.saveDirtyState}
      >
        <ModalContent>{content || spinner}</ModalContent>
      </Modal>
    ) : (
      <div class="ActivationContainer">{content || spinner}</div>
    );
  }
}
