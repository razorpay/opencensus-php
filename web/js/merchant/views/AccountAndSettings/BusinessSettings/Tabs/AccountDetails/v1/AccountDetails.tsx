import { Modules } from 'common/constant/enums';
import Popover, { PopoverBody } from 'common/ui/Popover';
import TextHighlighter from 'common/ui/TextHighlighter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import DetailRow from 'merchant/components/DetailRow';
import User from 'merchant/models/User';
import * as ProfileActions from 'merchant/reducers/profile';
import { updateSession } from 'merchant/reducers/session';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import MerchantConfigForm from 'merchant/views/Account/Profile/components/MerchantConfigForm';
import UserContactMobile from 'merchant/views/Account/Profile/components/UserContactMobile';
import {
  ACTION_QUERY_PARAM_KEY,
  EMAIL_UPDATE,
  UPDATE_DISPLAY_NAME,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { ContactDetailsProps } from 'merchant/views/AccountAndSettings/BusinessSettings/typings';
import { StyledTabContentContainer } from 'merchant/views/AccountAndSettings/styled';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

const ContactDetails = ({
  user,
  openModal,
  closeModal,
  showNotification,
  updateMerchantConfig,
  updateSession,
  isFlowRevamped = true,
  page,
}: ContactDetailsProps): JSX.Element => {
  const updateMerchantConfigFn = (args) => {
    return updateMerchantConfig(args)
      .then((resp) => {
        /* istanbul ignore else */
        if (resp.success) {
          analyticsTrack({
            objectName: 'display name update',
            actionName: 'status',
            screen: 'my account',
            properties: {
              status: 'success',
              newDisplayName: args.display_name,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          selfServeTrackSuccess({
            selfServeAction: 'Display Name Updated',
            page: user.isAccountAndSettingsRevampEnabled ? 'Contact details' : 'Profile',
            screen: user.isAccountAndSettingsRevampEnabled
              ? Modules.AccountAndSettings
              : Modules.MyAccount,
          });
          showNotification({
            type: 'success',
            message: 'Display name changed successfully.',
          });

          closeModal();

          const newUser = new User({
            ...user,
            display_name: resp.data.display_name,
          });

          updateSession({ user: newUser });
        }

        return resp;
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'display name update',
          actionName: 'status',
          screen: 'my account',
          properties: {
            status: 'failure',
            newDisplayName: args.display_name,
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const openAttrSaveModal = (attr) =>
    openModal({
      size: 'small',
      component: (
        <MerchantConfigForm
          attribute={attr}
          label={ATTR_DETAILS[attr].label}
          desc={ATTR_DETAILS[attr].desc}
          value={user[attr]}
          updateMerchantConfig={updateMerchantConfigFn}
        />
      ),
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: UPDATE_DISPLAY_NAME,
      },
    });

  const openChangeDisplayName = () => {
    selfServeTrackInitiate({
      selfServeAction: 'Display Name Updated',
      page: user.isAccountAndSettingsRevampEnabled ? 'Contact details' : 'Profile',
      screen: user.isAccountAndSettingsRevampEnabled
        ? Modules.AccountAndSettings
        : Modules.MyAccount,
    });
    openAttrSaveModal('display_name');
  };

  const labelHandler = (hashedWith, content) => (
    <TextHighlighter hashedWith={hashedWith}>{content}</TextHighlighter>
  );

  return (
    <StyledTabContentContainer className="content">
      <div
        data-testid="contact-details-section"
        className={`${isFlowRevamped ? 'list-group details-row-container' : ''}`}
      >
        <DetailRow label="Contact Name" value={titleCase(user.contact_name)} />

        {user.isAdminOrOwner && (
          <DetailRow
            label={() => (
              <div>
                <span>Display Name</span>
                <small className="help-content">
                  <i className="i i-info-outline" />
                  <Popover align="top" theme="dark">
                    <PopoverBody>
                      <div>
                        {user?.isOrgCurlec
                          ? ATTR_DETAILS.curlec_display_name.desc
                          : ATTR_DETAILS.display_name.desc}
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </div>
            )}
            value={() =>
              user.display_name ? (
                <span>
                  {user.display_name}
                  <a
                    className="p-l"
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'dispay name edit',
                        actionName: 'clicked',
                        screen: 'my account',
                        properties: {
                          action: 'reset',
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                      return openChangeDisplayName();
                    }}
                    title="Edit Display Name"
                    data-testid="Edit Display Name"
                  >
                    <i className="i i-edit" />
                  </a>
                </span>
              ) : (
                <a
                  className="p-l"
                  onClick={() => {
                    analyticsTrack({
                      objectName: 'display name edit',
                      actionName: 'clicked',
                      screen: 'my account',
                      properties: {
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    return openChangeDisplayName();
                  }}
                  title="Set Display Name"
                  data-testid="Set Display Name"
                >
                  Set Display Name
                </a>
              )
            }
          />
        )}
        <DetailRow
          label={() => labelHandler(EMAIL_UPDATE, 'Contact Email')}
          value={() => (
            <a
              onClick={() => {
                analyticsTrack({
                  objectName: 'contact email',
                  actionName: 'clicked',
                  screen: 'my account',
                  properties: {
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }}
              href={`mailto:${user.email}`}
            >
              {user.email}
            </a>
          )}
        />
        <UserContactMobile page={page} />
      </div>
    </StyledTabContentContainer>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ProfileActions,
      ...ModalActions,
      showNotification,
      updateSession,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(ContactDetails);
