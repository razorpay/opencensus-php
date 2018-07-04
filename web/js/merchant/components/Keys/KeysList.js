import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import Key from 'merchant/models/Key';
import { openModal, closeModal } from 'rzp/modules/modals';
// import RegenerateKey from 'merchant/models/Key'

import EditWebsiteDetails from 'merchant/containers/EditWebsiteDetails';

const KeysListItem = props => {
  let mode = props.mode;
  let { id, created_at, expired_at } = props.apiKey;
  return (
    <tr>
      <td>{id}</td>
      <td>
        <Time value={created_at} format={'MMM Do, YYYY hh:mm:ss A'} />
      </td>
      <td>
        {expired_at ? (
          <Time value={expired_at} format={'MMM Do, YYYY hh:mm:ss A'} />
        ) : (
          'Never'
        )}
      </td>
      <td>
        {expired_at ? (
          'None'
        ) : (
          <div class="row-action">
            <button
              class="btn btn-xs btn-primary"
              onClick={() => {
                props.showRollKeyModal({ id });
              }}
            >
              <i class="i i-refresh" />
              <span>Regenerate {mode} Key</span>
            </button>
          </div>
        )}
      </td>
    </tr>
  );
};

export default connect(null, { openModal, closeModal })(props => {
  let {
    mode,
    keys,
    isLoading,
    merchantId,
    hasKeyAccess,
    businessWebsite,
    showRollKeyModal = () => {},
    generateKey = () => {},
  } = props;

  let params = {
    merchantId: merchantId,
  };

  return (
    <div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Key Id</th>
              <th>Created At</th>
              <th>Expiry</th>
              <th>Action</th>
            </tr>
          </thead>
          <TableBody
            isLoading={isLoading}
            colSpan={4}
            rows={keys}
            emptyTableRow={
              <tr>
                <td class="text-center empty-table" colSpan={4}>
                  {mode === 'Test' || hasKeyAccess ? (
                    <React.Fragment>
                      {!hasKeyAccess && (
                        <p>
                          You can generate API keys in Test Mode. For generating
                          keys in Live Mode, you need to provide your business
                          website/app details while filling the activation form.
                        </p>
                      )}
                      <button
                        class="btn btn-primary"
                        onClick={() => {
                          generateKey(params);
                        }}
                      >
                        Generate {mode} Key
                      </button>
                    </React.Fragment>
                  ) : !businessWebsite ? (
                    <div>
                      <p
                      >{`Please provide your Business Website/App details in order to generate API keys in ${mode} Mode`}</p>
                      <button
                        class="btn btn-primary"
                        onClick={() =>
                          props.openModal({
                            size: 'small',
                            component: (
                              <EditWebsiteDetails onClose={props.closeModal} />
                            ),
                          })
                        }
                      >
                        Add Website/App URL
                      </button>
                    </div>
                  ) : (
                    <div>
                      The website/app details that you have provided are under
                      review. You can generate API keys once the details are
                      approved.
                    </div>
                  )}
                </td>
              </tr>
            }
          >
            {keys.map(key => (
              <KeysListItem
                key={key.id}
                apiKey={key}
                mode={mode}
                showRollKeyModal={showRollKeyModal}
              />
            ))}
          </TableBody>
        </table>
      </div>
    </div>
  );
});
