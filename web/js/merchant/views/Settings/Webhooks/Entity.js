import React, { Component } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';
import moment from 'moment';
import LoaderDots from 'common/ui/LoaderDots';
import Spinner from 'common/ui/Spinner';
import * as ModalActions from 'merchant_common/reducers/modals';
import SwitchField from 'common/ui/Forms/SwitchField';
import Definition from 'common/ui/Definition';
import Button from 'common/new-ui/Button';
import Alert from 'common/new-ui/Alert';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import PropTypes from 'prop-types';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import DocsLink from 'merchant/components/DocsLink';
import * as WebhookActions from 'merchant/reducers/webhooks';
import Collapsible from 'merchant/components/Collapsible';
import AddEditWebhook from './AddEditWebhook';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

class WebhookEntity extends Component {
  state = {
    toggleStatusLoading: false,
  };

  static contextTypes = {
    confirm: PropTypes.func,
  };

  showWebhookModal = (webhook = null) => {
    const tracking = this.props.tracking;
    selfServeTrackInitiate({
      selfServeAction: 'Webhook Edited',
      page: 'Webhooks',
      screen: 'Settings',
    });
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('Webhook.editIntiated', {
        webhook_id: webhook.id,
      }),
    );
    this.props.openModal({
      component: <AddEditWebhook webhook={webhook} />,
    });
  };

  UNSAFE_componentWillMount() {
    this.props.fetchWebhook({
      id: this.props.id,
    });
  }

  toggleActive = (isChecked, cb) => {
    this.setState({ toggleStatusLoading: true });
    const { webhooks } = this.props.webhooks;
    const webhook = webhooks.find((_webhook) => _webhook.id === this.props.id);
    const newWebhookData = {
      ...webhook,
      active: isChecked,
    };

    return this.props
      .saveWebhook(newWebhookData)
      .then(() => {
        cb(true);
        this.setState({ toggleStatusLoading: false });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: `${err.errors}`,
        });
        this.setState({ toggleStatusLoading: false });
      });
  };

  handleDelete = (webhooks) => {
    const webhook = webhooks.find((_webhook) => _webhook.id === this.props.id);
    this.context
      .confirm({
        header: 'Are you sure?',
        message: () => (
          <div className="text-semi-muted">
            You are about to permanently delete the webhook URL.
            <br />
            <br />
            <Alert.Warning>
              <b>Note: </b>
              You can try temporarily disabling a webhook.
            </Alert.Warning>
          </div>
        ),
        affirmativeLabel: 'Yes, Delete',
        affirmativePendingLabel: 'Deleting...',
        abortLabel: "No, don't!",
        action: () => {
          const webhookData = {
            id: webhook.id,
          };
          const tracking = this.props.tracking;
          return this.props
            .deleteWebhook(webhookData)
            .then(() => {
              const params = { skip: '0', count: '25' };
              this.props.fetchWebhooks(params);
              this.props.showNotification({
                type: 'success',
                message: 'Webhook deleted successfully',
              });
              this.props.history.goBack();
              tracking.trackEvent(
                window.rzpQ.merchantActions().success('Webhook.delete', {
                  webhook_id: webhook.id,
                  webhook_count: webhooks.length,
                }),
              );
            })
            .catch(() => {});
        },
      })
      .catch(() => {});
  };

  render() {
    const webhooksState = this.props.webhooks;
    const { loadingWebhook, webhooks } = webhooksState;
    const { userData } = this.props;
    const webhook = webhooks.find((_webhook) => _webhook.id === this.props.id);

    if (loadingWebhook || !webhook) {
      return (
        <div className="content-wrapper content-sm txn-details">
          <div className="page-spinner-container">
            <Spinner />
          </div>
        </div>
      );
    }

    const activeEvents = [];

    Object.keys(webhook.events).forEach((key) => {
      if (webhook.events[key] === true) {
        activeEvents.push(key);
      }
    });

    return (
      <div className="content-wrapper content-sm txn-details">
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <strong>Webhook Details</strong>
            <div className="webhook-actions">
              <button
                type="button"
                className="btn Button--primary--invert btn-lg delete-webhook"
                onClick={() => this.handleDelete(webhooks)}
              >
                Delete
              </button>
              <Button.Primary onClick={() => this.showWebhookModal(webhook)} type="button">
                Edit
              </Button.Primary>
            </div>
          </div>
          <div className="SliderPanel__Body">
            <div className="panel-body">
              <div className="list-group details-row-container">
                <EntityDetailRow label="Webhook URL" value={webhook.url} />
                <EntityDetailRow label="Status">
                  <span className="toggler-btn">
                    {this.state.toggleStatusLoading ? (
                      <LoaderDots />
                    ) : (
                      <>
                        <SwitchField
                          defaultChecked={webhook.active}
                          onChange={(isChecked, cb) => this.toggleActive(isChecked, cb)}
                          type="prime"
                          data-testid="webhook-toggle-switch"
                        />
                        {webhook.active ? (
                          <b className="text-primary" style={{ marginLeft: '4px' }}>
                            Enabled
                          </b>
                        ) : (
                          <b className="text-faded" style={{ marginLeft: '4px' }}>
                            Disabled
                          </b>
                        )}
                      </>
                    )}
                  </span>
                </EntityDetailRow>
                <EntityDetailRow label="Secret">
                  {webhook.secret_exists ? (
                    <p>Secret was provided during webhook setup</p>
                  ) : (
                    <p>Not provided</p>
                  )}
                  <DocsLink
                    title="Learn more about Webhook secrets"
                    url="https://razorpay.com/docs/webhooks/"
                    className="webhook-doclinks"
                  />
                </EntityDetailRow>
                <EntityDetailRow label="Active Events">
                  <Definition>
                    <p>{activeEvents.length} Active Events</p>
                    {activeEvents.slice(0, 7).map((event) => (
                      <p key={event}>{event}</p>
                    ))}
                    {activeEvents.length > 7 ? (
                      <div className="webhooks-collapsible-container">
                        <Collapsible
                          title={(collapsibleOpen) => (
                            <span className="text-primary">
                              {collapsibleOpen ? 'Hide some' : 'Show all'} active events
                            </span>
                          )}
                          childrenPosition="top"
                          className="CollapsibleFields"
                        >
                          {activeEvents.slice(7).map((event, idx) => (
                            <p key={idx}>{event}</p>
                          ))}
                        </Collapsible>
                      </div>
                    ) : null}
                  </Definition>
                </EntityDetailRow>
                <EntityDetailRow
                  label="Alert Email"
                  value={webhook.alert_email ? webhook.alert_email : userData.email}
                />
                {webhook.updated_at && webhook.updated_by_email ? (
                  <EntityDetailRow label="Last Updated By">
                    <Definition>
                      <p>{webhook.updated_by_email}</p>
                      <p>{moment(webhook.updated_at, 'X').format('DD MMM YYYY, hh:mm A')}</p>
                    </Definition>
                  </EntityDetailRow>
                ) : null}

                {webhook.created_at && webhook.created_by_email ? (
                  <EntityDetailRow label="Created By">
                    <Definition>
                      <p>{webhook.created_by_email}</p>
                      <p>{moment(webhook.created_at, 'X').format('DD MMM YYYY, hh:mm A')}</p>
                    </Definition>
                  </EntityDetailRow>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => {
      return {
        userData: state.session.user,
        webhooks: state.webhooks,
        modeFormatted: state.session.modeFormatted,
      };
    },
    { ...WebhookActions, ...ModalActions, ...NotificationsActions },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('WebhooksContainer')),
)(WebhookEntity);
