import { Component } from 'react';
import { connect } from 'react-redux';

import { Modal, ModalContent } from 'component/Modal';
import { classList } from 'common/util';
import Spinner from 'rzp/ui/Spinner';
import { merchantFetch } from 'merchant/utils/ajax';

import KycForm from './new';
import InstantActivation from './Instant';
import { setInstantActivationsTracking } from './ga_new';

@connect(state => ({ user: state.session.user, session: state.session }))
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
    this.handleNewData = this.handleNewData.bind(this);

    if (props.user.showInstantActivation) {
      setInstantActivationsTracking();
    }

    if (window.RZP && window.RZP.appName === 'businessbanking') {
      const commonActivationMethods = [
          {
            name: 'notifyWindowResize',
            hasReply: true,
            callback: reply => {
              this.handleUIUpdate = () => {
                const body = document.body;
                reply(body.clientWidth, body.clientHeight);
              };
            },
          },
        ],
        iaActivationMethods = [
          {
            name: 'submitForm',
            hasReply: true,
          },
          {
            name: 'notifyFormValidity',
            hasReply: true,
          },
        ],
        kycActivationMethods = [
          {
            name: 'notifyOnKYCSuccess',
            hasReply: true,
          },
        ];

      const { isL1Submitted } = props.user.instantActivation;

      this.rpc = window.RZP.rpcServer(
        window.RZP.appHost,
        (isL1Submitted ? kycActivationMethods : iaActivationMethods).concat(
          commonActivationMethods
        ),
        'activation'
      );
    }
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
      categories = categories && categories.data;

      this.setState({
        data,
        categories,
      });

      return [data, categories];
    });
  }

  handleNewData(data) {
    this.setState({ data });
  }

  componentWillMount() {
    this.fetchActivationDetails(this.props.accountId);
  }

  setOnCloseCb(cb) {
    this.onCloseCB = cb;
  }

  render() {
    const { data, categories, additionalModalClass } = this.state,
      { user } = this.props,
      commonProps = {
        accountId: this.props.accountId,
        fetchActivationDetails: this.fetchActivationDetails,
        data,
        categories,
        handleUIUpdate: this.handleUIUpdate,
        rpc: this.rpc,
      },
      isLoading = !data,
      // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
      isModal = !!this.props.onClose,
      { isL1Submitted, isBlacklistFlow } = user.instantActivation,
      showL1Modal =
        user.showInstantActivation && (!isL1Submitted || isBlacklistFlow);

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
      if (showL1Modal) {
        modalClasses = modalClasses.concat([
          'Activation--wizard',
          'Activation--wizard--Instant',
        ]);
        content = (
          <InstantActivation
            {...commonProps}
            onFormValidityChange={this.handleIAFormValidityChange}
          />
        );
      } else {
        content = (
          <KycForm
            {...commonProps}
            onNewData={this.handleNewData}
            setAdditionalModalClass={this.setAdditionalModalClass}
          />
        );
      }
    }

    if (additionalModalClass) {
      modalClasses.push(additionalModalClass);
    }

    return isModal ? (
      <div className={showL1Modal ? 'instant-activations-modal-container' : ''}>
        <Modal
          class={classList(...modalClasses)}
          onClose={this.props.onClose}
          onCloseCB={this.saveDirtyState}
        >
          <ModalContent>{content || spinner}</ModalContent>
        </Modal>
      </div>
    ) : (
      <div
        className={`ActivationContainer${showL1Modal ? ' instant' : ' kyc'}`}
      >
        {content || spinner}
      </div>
    );
  }
}
