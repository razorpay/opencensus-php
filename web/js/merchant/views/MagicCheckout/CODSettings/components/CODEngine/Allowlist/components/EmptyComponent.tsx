import React from 'react';
import { Button, UploadCloudIcon } from '@razorpay/blade/components';

export const EmptyComponent =
  (onUploadClick: () => void, txt: string, hasNoData: React.RefObject<boolean>) => () =>
    (
      <div className="empty-table-message">
        {hasNoData.current ? (
          <p>No result found!</p>
        ) : (
          <>
            <p>{`No ${txt} Set!`}</p>
            {onUploadClick && (
              <Button
                type="button"
                variant="primary"
                color="default"
                onClick={onUploadClick}
                size="medium"
                iconPosition="left"
                icon={UploadCloudIcon}
              >
                Upload Allowlist
              </Button>
            )}
          </>
        )}
      </div>
    );
