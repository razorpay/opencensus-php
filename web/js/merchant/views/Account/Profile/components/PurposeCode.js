import { connect } from 'react-redux';
import { useState, useEffect } from 'react';

import Popover, { PopoverBody } from 'common/ui/Popover';
import SelectPurposeCodeForm from 'merchant/views/Account/Profile/components/SelectPurposeCodeForm';
import * as modalActions from 'merchant_common/reducers/modals';
import editImg from '../../../../../../icons/merchant/edit_board.svg';
import { fetchPurposeCode } from 'merchant/reducers/profile';
import { showNotification } from 'merchant_common/reducers/notifications';

const PurposeCode = (props) => {
  const [purposeCode, setPurposeCode] = useState(null);
  const [codeDescription, setCodeDescription] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const userEmail = props.user.email;
    fetchPurposeCode(userEmail)
      .then((res) => {
        if (res?.data?.merchants) {
          const merchants = res.data.merchants;
          if (merchants.length > 0) {
            const merchantData = merchants[0];
            const fetchedPurposeCode = merchantData.purpose_code;
            const fetchedPurposeCodeDescription = merchantData.purpose_code_desc;
            setPurposeCode(fetchedPurposeCode);
            setCodeDescription(fetchedPurposeCodeDescription);
            setIsLoading(false);
          }
        }
      })
      .catch(() => {
        props.showNotification({
          type: 'error',
          message: 'Sorry! Could not fetch purpose code details',
        });
        setIsLoading(false);
      });
  }, []);

  const openSelectPurposeCodeModal = () => {
    props.openModal({
      size: 'medium',
      component: <SelectPurposeCodeForm onSetPurposeCode={setPurposeCodeAndDesc} />,
    });
  };

  const setPurposeCodeAndDesc = (code, desc) => {
    setPurposeCode(code);
    setCodeDescription(desc);
  };

  return (
    <div class="panel panel-default">
      <div class="panel-heading">Purpose Code and FIRC</div>
      <div class="list-group details-row-container">
        <div class="list-group-item">
          <span>
            Purpose Code
            <small class="help-content">
              <i class="i i-info-outline" />
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div>
                    Purpose Code is a code issued by Reserve Bank of India (RBI) to classify the
                    nature of inward foreign currency transaction.
                  </div>
                </PopoverBody>
              </Popover>
            </small>
          </span>
          <div>
            {isLoading ? (
              <span>Loading...</span>
            ) : purposeCode ? (
              <>
                <span>
                  <a>
                    <b>{purposeCode}</b>
                  </a>
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      <div>
                        <b>{purposeCode}</b> - {codeDescription}
                      </div>
                    </PopoverBody>
                  </Popover>
                </span>

                <img
                  src={editImg}
                  class="purpose-code-edit-button"
                  onClick={() => props.onEditClick()}
                />
              </>
            ) : (
              <>
                <a onClick={openSelectPurposeCodeModal}>Select Code</a>
                <Popover align="bottom" theme="dark">
                  <PopoverBody>
                    <div>
                      To get{' '}
                      <b>
                        <i>purpose code on FIRC</i>
                      </b>
                      , please update your code here.
                    </div>
                  </PopoverBody>
                </Popover>
              </>
            )}
          </div>
        </div>
        <div class="list-group-item">
          <span>FIRC Certificate</span>
          <div>Coming Soon</div>
        </div>
      </div>
      <div class="panel-heading background-highlight">
        <span class="text-bold">Note</span>: FIRC
        <span class="text-bold"> without purpose code </span> could lead to
        <span class="text-bold">
          <span class="text-danger"> rejections</span> on GST refunds
        </span>
        . Update your purpose code now!
      </div>
    </div>
  );
};

export default connect(null, { ...modalActions, showNotification })(PurposeCode);
