import AsyncButton from 'react-async-button';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { VirtualAccountStatusLabel } from 'merchant/components/StatusLabel';
import DetailRow from 'merchant/components/DetailRow';
import NestedDetailRow from 'merchant/components/NestedDetailRow';

export default props => {
  let { virtualaccount, isLoading, statusMsg, onClose, onDelete } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              Virtual Account ID: <b>{virtualaccount.id}</b>

              <div class="btn-toolbar pull-right">
                <Dropdown>
                  <DropdownTrigger class="dropdown-toggle">
                    <button
                      class="btn btn-default btn-sm dropdown-toggle"
                      type="button"
                    >
                      Actions {' '}
                      <span class="caret" />
                    </button>
                  </DropdownTrigger>
                  <DropdownContent>
                    <ul class="dropdown-menu pull-right">
                      {virtualaccount.status !== 'closed'
                        ? <li>
                            <a onClick={() => onClose(virtualaccount)}>
                              Close Account
                            </a>
                          </li>
                        : null}
                      <li>
                        <a onClick={() => onDelete(virtualaccount)}>
                          Delete Account
                        </a>
                      </li>
                    </ul>
                  </DropdownContent>
                </Dropdown>
              </div>
            </div>

            <div class="SliderPanel__Body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <DetailRow label="Name" value={virtualaccount.name} />
                  <DetailRow
                    label="Descriptor"
                    value={virtualaccount.descriptor}
                  />

                  <DetailRow
                    label="Amount Paid"
                    value={() => <Amount value={virtualaccount.amount_paid} />}
                  />

                  <DetailRow
                    label="Status"
                    value={() => (
                      <VirtualAccountStatusLabel
                        status={virtualaccount.status}
                      />
                    )}
                  />

                  <NestedDetailRow
                    label="Bank Account Details"
                    value={virtualaccount.bank_account}
                  />

                  <DetailRow
                    label="Created At"
                    value={() => (
                      <Time
                        value={virtualaccount.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />
                    )}
                  />
                </div>
              </div>
            </div>
          </div>}
    </div>
  );
};
