import React, { useEffect } from 'react';
import { connect } from 'react-redux';

// components
import Loader from 'common/components/Loader';
import Button from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';

// actions
import { fetchMerchantHSCode } from 'merchant/reducers/profile';

interface HSCodeProps {
  hsCode?: {
    loading: boolean;
    data: null | string;
    error: null | string[];
  };
  onClick?: () => void;
  getHSCodeDetails?: () => void;
}

const HSCode = ({ hsCode, onClick, getHSCodeDetails }: HSCodeProps) => {
  useEffect(() => {
    getHSCodeDetails?.();
  }, [getHSCodeDetails]);

  if (hsCode?.loading) {
    return (
      <div>
        <Loader />
      </div>
    );
  }

  return (
    <div>
      {hsCode?.data ? (
        <>
          <span>{hsCode.data}</span>

          <Button.Transparent onClick={onClick}>
            <i className="i i-edit p-l" />
          </Button.Transparent>
        </>
      ) : (
        <>
          <a onClick={onClick}>Select Code</a>
          <Popover align="top" theme="dark">
            <PopoverBody>
              <span>Update your HSCode here</span>
            </PopoverBody>
          </Popover>
        </>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({ hsCode: state.profile.hsCodeDetails });

const mapDispatchToProps = {
  getHSCodeDetails: fetchMerchantHSCode,
};

export default connect(mapStateToProps, mapDispatchToProps)(HSCode);
