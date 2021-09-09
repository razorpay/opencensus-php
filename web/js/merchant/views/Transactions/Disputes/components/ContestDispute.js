import React from 'react';
import PropTypes from 'prop-types';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import { connect } from 'react-redux';
import CurrencyField from './CurrencyField';
import EvidenceUpload from './EvidenceUpload';
import { analyticsTrack } from 'common/utils/analytics';
import { fetchDisputes } from 'merchant/reducers/collection';
import { fetchOpen, contest } from 'merchant/reducers/disputes/details';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { rupeesToPaise, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import roleList from 'merchant/helpers/permissions/roles-list';

const ContestDispute = (props) => {
  const {
    dispute,
    onCancelContest,
    dispatch,
    showNotification,
    fileTypesMap,
    user: { role },
  } = props;
  const isDipsuteOpen = dispute.status === 'open';

  const canUserTakeAction = [
    roleList.OWNER,
    roleList.ADMIN,
    roleList.MANAGER,
    roleList.OPERATIONS,
    roleList.FINANCE,
  ].includes(role);

  const submitEvidence = (data) => {
    if (!canUserTakeAction) {
      return null;
    }
    if (data?.amount?.includes('.00')) {
      data.amount = rupeesToPaise(data.amount);
    }
    let canUserSubmit = false;

    if (dispute.evidence)
      Object.keys(fileTypesMap).forEach((file) => {
        if (dispute.evidence[file]) {
          canUserSubmit = true;
        }
      });

    if (canUserSubmit) {
      analyticsTrack({
        objectName: 'dispute presentment',
        actionName: 'submit',
        screen: 'disputes',
        properties: {
          timestamp: Date.now(),
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      dispatch(contest(dispute.id, { ...data, action: 'submit' }))
        .then((_) => {
          analyticsTrack({
            objectName: 'dispute presentment',
            actionName: 'submit success',
            screen: 'disputes',
            properties: {
              timestamp: Date.now(),
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          dispatch(fetchOpen());
          dispatch(fetchDisputes({ skip: 0, count: 25 }));
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err.errors || err,
          });
        });
    } else {
      showNotification({
        type: 'error',
        message: 'Please upload atleast one evidence document to support your claim',
      });
    }
    return null;
  };

  const saveAsDraft = (data) => {
    if (!canUserTakeAction) {
      return null;
    }
    return dispatch(contest(dispute.id, { ...data, action: 'draft' })).catch((err) => {
      showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  };

  const handleInput = (e) => {
    let { value } = e.target;
    const { name } = e.target;
    if (name === 'amount') {
      value = rupeesToPaise(value);
    }
    if (isDipsuteOpen) saveAsDraft({ [name]: value, action: 'draft' });
  };

  return (
    <div id="contest-dispute">
      <hr />
      <div class="subheading p-l">Contest dispute</div>

      <Form
        onSubmit={submitEvidence}
        validator={(data) => {
          if (data.amount && data.summary) {
            return false;
          } else {
            return true;
          }
        }}
      >
        <EntityDetailRow label="Dispute Amount">
          <CurrencyField
            dispute={dispute}
            handleInput={handleInput}
            disabled={!canUserTakeAction}
          />
        </EntityDetailRow>

        <EntityDetailRow label="Explanation">
          <Input.Textarea
            key="summary"
            name="summary"
            required
            disabled={!canUserTakeAction}
            defaultValue={dispute?.evidence?.summary}
            readOnly={dispute.status !== 'open'}
            placeholder="Reason why the dispute is invalid..."
            onBlur={handleInput}
          />
        </EntityDetailRow>

        <hr />
        <div class="p-l bold">Supporting evidence</div>
        <p class="m-t p-l">
          Please upload supporting evidence like Invoice or Reciept / Proof of delivery / Customer
          signature, etc. The supported document types are: PDF, PNG and JPG. Click{' '}
          <a
            target="_blank"
            rel="noopener noreferrer"
            href="https://razorpay.com/docs/payments/disputes/presentments/dashboard/#contest-disputes-and-submit-evidence"
          >
            here
          </a>{' '}
          to know more.
        </p>

        <EvidenceUpload
          dispute={dispute}
          saveAsDraft={saveAsDraft}
          showNotification={showNotification}
          canUserTakeAction={canUserTakeAction}
        />

        {isDipsuteOpen && (
          <div class="dispute-cta">
            <button class="btn btn-primary" type="submit" disabled={!canUserTakeAction}>
              Submit Evidence
            </button>
            <button
              class="btn btn-outline"
              type="button"
              disabled={!canUserTakeAction}
              onClick={() => {
                analyticsTrack({
                  objectName: 'dispute presentment',
                  actionName: 'cancel contest',
                  screen: 'disputes',
                  properties: {
                    timestamp: Date.now(),
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                onCancelContest();
              }}
            >
              Cancel & accept dispute
            </button>
          </div>
        )}
      </Form>
    </div>
  );
};

ContestDispute.propTypes = {
  dispute: PropTypes.object.isRequired,
  onCancelContest: PropTypes.func.isRequired,
  dispatch: PropTypes.func.isRequired,
  showNotification: PropTypes.func.isRequired,
};

export default connect(
  (state) => ({ fileTypesMap: state.dispute.fileTypesMap, user: state.session.user }),
  (dispatch) => ({
    dispatch,
  }),
)(ContestDispute);
